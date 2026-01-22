<?php

namespace App\Console\Commands;

use App\Models\PriceHistory;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLegacyPriceHistory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'upm:import-legacy
                            {path : Path to the legacy SQLite database file}
                            {--dry-run : Show what would be imported without actually importing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import and deduplicate price history from legacy SQLite database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dbPath = $this->argument('path');
        $dryRun = $this->option('dry-run');

        if (!file_exists($dbPath)) {
            $this->error("Database file not found: {$dbPath}");

            return Command::FAILURE;
        }

        $this->info('Connecting to legacy database...');

        // Connect to legacy SQLite database
        config(['database.connections.legacy' => [
            'driver' => 'sqlite',
            'database' => $dbPath,
            'prefix' => '',
        ]]);

        try {
            $totalRecords = DB::connection('legacy')
                ->table('price_history')
                ->count();
            $this->info("Found {$totalRecords} total records in legacy database.");
        } catch (\Exception $e) {
            $this->error("Failed to connect to legacy database: {$e->getMessage()}");

            return Command::FAILURE;
        }

        // Get unique product/priceGroup combinations
        $legacyProducts = DB::connection('legacy')
            ->table('price_history')
            ->select('productId', 'priceGroup')
            ->distinct()
            ->get();

        $this->info("Found {$legacyProducts->count()} unique product/priceGroup combinations.");
        $this->newLine();

        if ($dryRun) {
            $this->warn('=== DRY RUN MODE - No data will be written ===');
            $this->newLine();
        }

        $bar = $this->output->createProgressBar($legacyProducts->count());
        $bar->start();

        $stats = [
            'products_processed' => 0,
            'products_matched' => 0,
            'products_not_found' => 0,
            'products_updated' => 0,
            'price_changes_found' => 0,
            'records_imported' => 0,
            'records_skipped_duplicate' => 0,
        ];

        $notFoundProducts = [];
        $updatedProductIds = [];

        foreach ($legacyProducts as $legacyProduct) {
            $stats['products_processed']++;

            // Find matching product in new database
            $product = Product::where('product_id', $legacyProduct->productId)
                ->where('price_group', $legacyProduct->priceGroup)
                ->first();

            if (!$product) {
                $stats['products_not_found']++;
                $notFoundProducts[] = "{$legacyProduct->productId}/{$legacyProduct->priceGroup}";
                $bar->advance();

                continue;
            }

            $stats['products_matched']++;

            // Get all price history for this product, ordered by datetime
            $histories = DB::connection('legacy')
                ->table('price_history')
                ->where('productId', $legacyProduct->productId)
                ->where('priceGroup', $legacyProduct->priceGroup)
                ->orderBy('datetime', 'asc')
                ->get();

            // Extract only price changes (deduplicate consecutive same prices)
            $priceChanges = $this->extractPriceChanges($histories);
            $stats['price_changes_found'] += count($priceChanges);

            $importedForThisProduct = false;

            // Import price changes
            foreach ($priceChanges as $change) {
                // Check if this exact record already exists
                $exists = PriceHistory::where('product_id', $product->id)
                    ->where('price', $change['price'])
                    ->where('created_at', $change['datetime'])
                    ->exists();

                if ($exists) {
                    $stats['records_skipped_duplicate']++;

                    continue;
                }

                if (!$dryRun) {
                    // Use DB::table to bypass Eloquent's automatic timestamp handling
                    DB::table('price_histories')->insert([
                        'product_id' => $product->id,
                        'price' => $change['price'],
                        'created_at' => $change['datetime'],
                        'updated_at' => $change['datetime'],
                    ]);
                }

                $stats['records_imported']++;
                $importedForThisProduct = true;
            }

            // Track products that need price stats update
            if ($importedForThisProduct) {
                $updatedProductIds[] = $product->id;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Update product price statistics
        if (!empty($updatedProductIds)) {
            $this->info('Updating product price statistics...');
            $stats['products_updated'] = $this->updateProductPriceStats($updatedProductIds, $dryRun);
        }

        $this->newLine();

        // Display results
        $this->info('Import completed!');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total records in legacy DB', $totalRecords],
                ['Unique products processed', $stats['products_processed']],
                ['Products matched', $stats['products_matched']],
                ['Products not found', $stats['products_not_found']],
                ['Price changes detected', $stats['price_changes_found']],
                ['Records imported', $stats['records_imported']],
                ['Duplicates skipped', $stats['records_skipped_duplicate']],
                ['Products stats updated', $stats['products_updated']],
            ]
        );

        // Calculate compression ratio
        if ($totalRecords > 0) {
            $ratio = round((1 - $stats['price_changes_found'] / $totalRecords) * 100, 2);
            $this->newLine();
            $this->info("Data compression: {$ratio}% reduction ({$totalRecords} -> {$stats['price_changes_found']} records)");
        }

        // Show not found products if any
        if (!empty($notFoundProducts) && count($notFoundProducts) <= 20) {
            $this->newLine();
            $this->warn('Products not found in current database:');
            foreach ($notFoundProducts as $p) {
                $this->line("  - {$p}");
            }
        } elseif (count($notFoundProducts) > 20) {
            $this->newLine();
            $this->warn("Products not found: {$stats['products_not_found']} (too many to list)");
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn('=== DRY RUN COMPLETE - Run without --dry-run to import ===');
        }

        return Command::SUCCESS;
    }

    /**
     * Extract only the records where price actually changed.
     *
     * @param \Illuminate\Support\Collection $histories
     */
    private function extractPriceChanges($histories): array
    {
        $changes = [];
        $lastPrice = null;

        foreach ($histories as $record) {
            if ($lastPrice === null || $record->price !== $lastPrice) {
                $changes[] = [
                    'price' => $record->price,
                    'datetime' => $record->datetime,
                ];
                $lastPrice = $record->price;
            }
        }

        return $changes;
    }

    /**
     * Update product price statistics (current_price, lowest_price, highest_price)
     * based on all price histories.
     *
     * @return int Number of products updated
     */
    private function updateProductPriceStats(array $productIds, bool $dryRun): int
    {
        $updated = 0;

        $bar = $this->output->createProgressBar(count($productIds));
        $bar->start();

        foreach ($productIds as $productId) {
            $product = Product::find($productId);
            if (!$product) {
                $bar->advance();

                continue;
            }

            // Get all price histories for this product
            $priceStats = DB::table('price_histories')
                ->where('product_id', $productId)
                ->selectRaw('MIN(price) as lowest, MAX(price) as highest')
                ->first();

            // Get the most recent price (current price)
            $latestHistory = DB::table('price_histories')
                ->where('product_id', $productId)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($priceStats && $latestHistory) {
                $newLowest = (int)$priceStats->lowest;
                $newHighest = (int)$priceStats->highest;
                $newCurrent = (int)$latestHistory->price;

                // Only update if values actually changed
                if (
                    $product->lowest_price !== $newLowest ||
                    $product->highest_price !== $newHighest ||
                    $product->current_price !== $newCurrent
                ) {
                    if (!$dryRun) {
                        $product->update([
                            'current_price' => $newCurrent,
                            'lowest_price' => $newLowest,
                            'highest_price' => $newHighest,
                        ]);
                    }
                    $updated++;
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        return $updated;
    }
}
