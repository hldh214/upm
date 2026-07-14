<?php

namespace Tests\Feature;

use App\Models\PriceHistory;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CrawlPricesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_executes_successfully(): void
    {
        Http::fake([
            'www.uniqlo.com/*' => Http::response([
                'result' => [
                    'items' => [
                        [
                            'productId' => 'E123456',
                            'priceGroup' => '000',
                            'name' => 'Test Product',
                            'prices' => ['base' => ['value' => 1990]],
                            'genderCategory' => 'MEN',
                            'images' => ['main' => null],
                        ],
                    ],
                    'pagination' => ['total' => 1],
                ],
            ], 200),
            'www.gu-global.com/*' => Http::response([
                'result' => [
                    'items' => [],
                    'pagination' => ['total' => 0],
                ],
            ], 200),
        ]);

        $this->artisan('upm:crawl')
            ->expectsOutput('Starting price crawl...')
            ->expectsOutput('Crawl completed!')
            ->assertExitCode(0);
    }

    public function test_command_accepts_brand_option(): void
    {
        Http::fake([
            'www.uniqlo.com/*' => Http::response([
                'result' => [
                    'items' => [
                        [
                            'productId' => 'E123456',
                            'priceGroup' => '000',
                            'name' => 'Uniqlo Product',
                            'prices' => ['base' => ['value' => 1990]],
                            'genderCategory' => 'MEN',
                            'images' => ['main' => null],
                        ],
                    ],
                    'pagination' => ['total' => 1],
                ],
            ], 200),
        ]);

        $this->artisan('upm:crawl --brand=uniqlo')
            ->assertExitCode(0);

        $this->assertDatabaseHas('products', ['brand' => 'uniqlo']);
        $this->assertDatabaseMissing('products', ['brand' => 'gu']);
    }

    public function test_command_rejects_invalid_brand(): void
    {
        $this->artisan('upm:crawl --brand=invalid')
            ->expectsOutput('Invalid brand parameter. Please use uniqlo or gu.')
            ->assertExitCode(1);
    }

    public function test_command_displays_results_table(): void
    {
        Http::fake([
            'www.uniqlo.com/*' => Http::response([
                'result' => [
                    'items' => [
                        [
                            'productId' => 'E111111',
                            'priceGroup' => '000',
                            'name' => 'Product 1',
                            'prices' => ['base' => ['value' => 1990]],
                            'genderCategory' => 'MEN',
                            'images' => ['main' => null],
                        ],
                        [
                            'productId' => 'E222222',
                            'priceGroup' => '000',
                            'name' => 'Product 2',
                            'prices' => ['base' => ['value' => 2990]],
                            'genderCategory' => 'WOMEN',
                            'images' => ['main' => null],
                        ],
                    ],
                    'pagination' => ['total' => 2],
                ],
            ], 200),
            'www.gu-global.com/*' => Http::response([
                'result' => [
                    'items' => [],
                    'pagination' => ['total' => 0],
                ],
            ], 200),
        ]);

        $this->artisan('upm:crawl')
            ->expectsTable(
                ['Metric', 'Count'],
                [
                    ['Total Products', 2],
                    ['New Products', 2],
                    ['Updated Products', 0],
                    ['Unchanged Products', 0],
                ]
            )
            ->assertExitCode(0);
    }

    public function test_command_displays_unchanged_products_count(): void
    {
        $product = Product::factory()->uniqlo()->create([
            'product_id' => 'E123456',
            'price_group' => '000',
            'name' => 'Same Product',
            'gender' => 'MEN',
            'image_url' => null,
            'current_price' => 1990,
            'lowest_price' => 1990,
            'highest_price' => 3990,
        ]);
        PriceHistory::create([
            'product_id' => $product->id,
            'price' => 1990,
        ]);

        Http::fake([
            'www.uniqlo.com/*' => Http::response([
                'result' => [
                    'items' => [
                        [
                            'productId' => 'E123456',
                            'priceGroup' => '000',
                            'name' => 'Same Product',
                            'prices' => ['base' => ['value' => 1990]],
                            'genderCategory' => 'MEN',
                            'images' => ['main' => null],
                        ],
                    ],
                    'pagination' => ['total' => 1],
                ],
            ], 200),
        ]);

        $this->artisan('upm:crawl --brand=uniqlo')
            ->expectsTable(
                ['Metric', 'Count'],
                [
                    ['Total Products', 1],
                    ['New Products', 0],
                    ['Updated Products', 0],
                    ['Unchanged Products', 1],
                ]
            )
            ->assertExitCode(0);
    }
}
