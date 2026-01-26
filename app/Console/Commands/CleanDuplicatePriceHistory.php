<?php

namespace App\Console\Commands;

use App\Models\PriceHistory;
use Illuminate\Console\Command;

class CleanDuplicatePriceHistory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'upm:clean-history 
                            {--dry-run : Show what would be deleted without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove duplicate consecutive price history records, keeping the older one';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        $this->info('Scanning for duplicate consecutive price records...');

        // Get all product IDs that have price history
        $productIds = PriceHistory::select('product_id')
            ->distinct()
            ->pluck('product_id');

        $this->info("Found {$productIds->count()} products with price history");

        $totalDeleted = 0;
        $progressBar = $this->output->createProgressBar($productIds->count());
        $progressBar->start();

        foreach ($productIds as $productId) {
            $deleted = $this->cleanProductHistory($productId, $dryRun);
            $totalDeleted += $deleted;
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        if ($dryRun) {
            $this->warn("DRY RUN: Would delete {$totalDeleted} duplicate records");
            $this->info('Run without --dry-run to apply changes');
        } else {
            $this->info("Successfully deleted {$totalDeleted} duplicate records");
        }

        return Command::SUCCESS;
    }

    /**
     * Clean duplicate consecutive price records for a single product.
     * Keeps the older record when prices are the same.
     */
    private function cleanProductHistory(int $productId, bool $dryRun): int
    {
        // Get all history records ordered by created_at ascending
        $histories = PriceHistory::where('product_id', $productId)
            ->orderBy('created_at', 'asc')
            ->get();

        if ($histories->count() < 2) {
            return 0;
        }

        $idsToDelete = [];
        $previousPrice = null;

        foreach ($histories as $history) {
            if ($previousPrice !== null && $history->price === $previousPrice) {
                // This record has the same price as the previous one - mark for deletion
                $idsToDelete[] = $history->id;
            } else {
                // Price changed, update the previous price
                $previousPrice = $history->price;
            }
        }

        if (! empty($idsToDelete) && ! $dryRun) {
            PriceHistory::whereIn('id', $idsToDelete)->delete();
        }

        return count($idsToDelete);
    }
}
