<?php

namespace App\Console\Commands;

use App\Services\PriceCrawlerService;
use Illuminate\Console\Command;

class CrawlPrices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'upm:crawl {--brand= : Specify brand (uniqlo/gu), default crawls all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crawl Uniqlo/GU product price data';

    /**
     * Execute the console command.
     */
    public function handle(PriceCrawlerService $crawler): int
    {
        $brand = $this->option('brand');

        if ($brand && !in_array($brand, ['uniqlo', 'gu'])) {
            $this->error('Invalid brand parameter. Please use uniqlo or gu.');
            return Command::FAILURE;
        }

        $this->info('Starting price crawl...');

        $results = $crawler->crawl($brand);

        $this->newLine();
        $this->info('Crawl completed!');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Products', $results['total']],
                ['New Products', $results['created']],
                ['Updated Products', $results['updated']],
            ]
        );

        if (!empty($results['errors'])) {
            $this->newLine();
            $this->warn('Errors:');
            foreach ($results['errors'] as $error) {
                $this->error($error);
            }
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
