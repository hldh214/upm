<?php

namespace App\Console\Commands;

use App\Models\PriceHistory;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixProductPriceStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'upm:fix-price-stats 
                            {--dry-run : Show what would be fixed without making changes}
                            {--product= : Fix a specific product by ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix products highest_price and lowest_price based on price_histories table';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $productId = $this->option('product');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        $this->info('Calculating correct price stats from price_histories...');

        // Get aggregated stats from price_histories
        $query = PriceHistory::select(
            'product_id',
            DB::raw('MIN(price) as correct_lowest'),
            DB::raw('MAX(price) as correct_highest')
        )
            ->groupBy('product_id');

        if ($productId) {
            $query->where('product_id', $productId);
        }

        $priceStats = $query->get()->keyBy('product_id');

        $this->info("Found price history for {$priceStats->count()} products");

        // Get products that need fixing
        $productsQuery = Product::query();
        if ($productId) {
            $productsQuery->where('id', $productId);
        }

        $products = $productsQuery->get();
        $fixedCount = 0;
        $mismatchedProducts = [];

        $progressBar = $this->output->createProgressBar($products->count());
        $progressBar->start();

        foreach ($products as $product) {
            $stats = $priceStats->get($product->id);

            if (! $stats) {
                // No price history for this product
                $progressBar->advance();

                continue;
            }

            $correctLowest = $stats->correct_lowest;
            $correctHighest = $stats->correct_highest;

            $needsFix = false;
            $changes = [];

            if ($product->lowest_price != $correctLowest) {
                $needsFix = true;
                $changes['lowest_price'] = [
                    'from' => $product->lowest_price,
                    'to' => $correctLowest,
                ];
            }

            if ($product->highest_price != $correctHighest) {
                $needsFix = true;
                $changes['highest_price'] = [
                    'from' => $product->highest_price,
                    'to' => $correctHighest,
                ];
            }

            if ($needsFix) {
                $mismatchedProducts[] = [
                    'id' => $product->id,
                    'product_id' => $product->product_id,
                    'name' => mb_substr($product->name, 0, 30),
                    'changes' => $changes,
                ];

                if (! $dryRun) {
                    $product->update([
                        'lowest_price' => $correctLowest,
                        'highest_price' => $correctHighest,
                    ]);
                }

                $fixedCount++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        if (empty($mismatchedProducts)) {
            $this->info('All products have correct price stats. No fixes needed.');

            return Command::SUCCESS;
        }

        // Display mismatched products
        $this->warn("Found {$fixedCount} products with incorrect price stats:");
        $this->newLine();

        foreach ($mismatchedProducts as $item) {
            $this->line("Product ID: {$item['id']} ({$item['product_id']}) - {$item['name']}");
            foreach ($item['changes'] as $field => $change) {
                $this->line("  {$field}: {$change['from']} -> {$change['to']}");
            }
        }

        $this->newLine();

        if ($dryRun) {
            $this->warn("DRY RUN: Would have fixed {$fixedCount} products");
            $this->info('Run without --dry-run to apply fixes');
        } else {
            $this->info("Successfully fixed {$fixedCount} products");
        }

        return Command::SUCCESS;
    }
}
