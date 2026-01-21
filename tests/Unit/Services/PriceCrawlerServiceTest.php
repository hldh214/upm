<?php

namespace Tests\Unit\Services;

use App\Models\Product;
use App\Models\PriceHistory;
use App\Services\PriceCrawlerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PriceCrawlerServiceTest extends TestCase
{
    use RefreshDatabase;

    private PriceCrawlerService $crawler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crawler = new PriceCrawlerService();
    }

    public function test_crawl_creates_new_products(): void
    {
        Http::fake([
            'www.uniqlo.com/*' => Http::response([
                'result' => [
                    'items' => [
                        [
                            'productId' => 'E123456',
                            'priceGroup' => '000',
                            'name' => 'Test T-Shirt',
                            'prices' => ['base' => ['value' => 1990]],
                            'genderCategory' => 'MEN',
                            'images' => ['main' => 'https://example.com/image.jpg'],
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

        $results = $this->crawler->crawl();

        $this->assertEquals(1, $results['total']);
        $this->assertEquals(1, $results['created']);
        $this->assertEquals(0, $results['updated']);
        $this->assertEmpty($results['errors']);

        $this->assertDatabaseHas('products', [
            'product_id' => 'E123456',
            'price_group' => '000',
            'name' => 'Test T-Shirt',
            'brand' => 'uniqlo',
            'current_price' => 1990,
        ]);
    }

    public function test_crawl_updates_existing_products(): void
    {
        $product = Product::factory()->create([
            'product_id' => 'E123456',
            'price_group' => '000',
            'brand' => 'uniqlo',
            'name' => 'Old Name',
            'current_price' => 2990,
            'lowest_price' => 2990,
            'highest_price' => 2990,
        ]);

        Http::fake([
            'www.uniqlo.com/*' => Http::response([
                'result' => [
                    'items' => [
                        [
                            'productId' => 'E123456',
                            'priceGroup' => '000',
                            'name' => 'New Name',
                            'prices' => ['base' => ['value' => 1990]],
                            'genderCategory' => 'MEN',
                            'images' => ['main' => 'https://example.com/image.jpg'],
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

        $results = $this->crawler->crawl();

        $this->assertEquals(1, $results['total']);
        $this->assertEquals(0, $results['created']);
        $this->assertEquals(1, $results['updated']);

        $product->refresh();
        $this->assertEquals('New Name', $product->name);
        $this->assertEquals(1990, $product->current_price);
        $this->assertEquals(1990, $product->lowest_price); // Updated since lower
        $this->assertEquals(2990, $product->highest_price); // Unchanged
    }

    public function test_crawl_records_price_history(): void
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

        $this->crawler->crawl();

        $this->assertDatabaseCount('price_histories', 1);
        $this->assertDatabaseHas('price_histories', [
            'price' => 1990,
        ]);
    }

    public function test_crawl_creates_new_price_history_on_each_crawl(): void
    {
        $product = Product::factory()->create([
            'product_id' => 'E123456',
            'price_group' => '000',
            'brand' => 'uniqlo',
        ]);

        PriceHistory::create([
            'product_id' => $product->id,
            'price' => 2990,
        ]);

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

        $this->crawler->crawl();

        // Should now have 2 price history records (multiple crawls per day allowed)
        $this->assertDatabaseCount('price_histories', 2);
    }

    public function test_crawl_specific_brand_only(): void
    {
        Http::fake([
            'www.uniqlo.com/*' => Http::response([
                'result' => [
                    'items' => [
                        [
                            'productId' => 'E111111',
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
            'www.gu-global.com/*' => Http::response([
                'result' => [
                    'items' => [
                        [
                            'productId' => 'E222222',
                            'priceGroup' => '000',
                            'name' => 'GU Product',
                            'prices' => ['base' => ['value' => 990]],
                            'genderCategory' => 'WOMEN',
                            'images' => ['main' => null],
                        ],
                    ],
                    'pagination' => ['total' => 1],
                ],
            ], 200),
        ]);

        // Only crawl uniqlo
        $results = $this->crawler->crawl('uniqlo');

        $this->assertEquals(1, $results['total']);
        $this->assertDatabaseHas('products', ['brand' => 'uniqlo']);
        $this->assertDatabaseMissing('products', ['brand' => 'gu']);
    }

    public function test_crawl_handles_pagination(): void
    {
        Http::fake([
            'www.uniqlo.com/*' => function ($request) {
                $offset = $request->data()['offset'] ?? 0;
                if ($offset == 0) {
                    return Http::response([
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
                            ],
                            'pagination' => ['total' => 101], // More than PAGINATION_LIMIT
                        ],
                    ], 200);
                } else {
                    return Http::response([
                        'result' => [
                            'items' => [
                                [
                                    'productId' => 'E222222',
                                    'priceGroup' => '000',
                                    'name' => 'Product 2',
                                    'prices' => ['base' => ['value' => 2990]],
                                    'genderCategory' => 'WOMEN',
                                    'images' => ['main' => null],
                                ],
                            ],
                            'pagination' => ['total' => 101],
                        ],
                    ], 200);
                }
            },
            'www.gu-global.com/*' => Http::response([
                'result' => [
                    'items' => [],
                    'pagination' => ['total' => 0],
                ],
            ], 200),
        ]);

        $results = $this->crawler->crawl('uniqlo');

        $this->assertEquals(2, $results['total']);
        $this->assertDatabaseCount('products', 2);
    }

    public function test_crawl_skips_items_with_missing_required_fields(): void
    {
        Http::fake([
            'www.uniqlo.com/*' => Http::response([
                'result' => [
                    'items' => [
                        // Missing productId
                        [
                            'priceGroup' => '000',
                            'name' => 'Invalid Product 1',
                            'prices' => ['base' => ['value' => 1990]],
                        ],
                        // Missing priceGroup
                        [
                            'productId' => 'E222222',
                            'name' => 'Invalid Product 2',
                            'prices' => ['base' => ['value' => 1990]],
                        ],
                        // Missing price
                        [
                            'productId' => 'E333333',
                            'priceGroup' => '000',
                            'name' => 'Invalid Product 3',
                        ],
                        // Valid product
                        [
                            'productId' => 'E444444',
                            'priceGroup' => '000',
                            'name' => 'Valid Product',
                            'prices' => ['base' => ['value' => 1990]],
                            'genderCategory' => 'MEN',
                            'images' => ['main' => null],
                        ],
                    ],
                    'pagination' => ['total' => 4],
                ],
            ], 200),
            'www.gu-global.com/*' => Http::response([
                'result' => [
                    'items' => [],
                    'pagination' => ['total' => 0],
                ],
            ], 200),
        ]);

        $results = $this->crawler->crawl('uniqlo');

        $this->assertEquals(1, $results['created']);
        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseHas('products', ['product_id' => 'E444444']);
    }

    public function test_crawl_updates_lowest_price_when_price_drops(): void
    {
        $product = Product::factory()->create([
            'product_id' => 'E123456',
            'price_group' => '000',
            'brand' => 'uniqlo',
            'current_price' => 2990,
            'lowest_price' => 2490,
            'highest_price' => 3990,
        ]);

        Http::fake([
            'www.uniqlo.com/*' => Http::response([
                'result' => [
                    'items' => [
                        [
                            'productId' => 'E123456',
                            'priceGroup' => '000',
                            'name' => 'Test Product',
                            'prices' => ['base' => ['value' => 1990]], // Lower than lowest
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

        $this->crawler->crawl('uniqlo');

        $product->refresh();
        $this->assertEquals(1990, $product->current_price);
        $this->assertEquals(1990, $product->lowest_price); // Updated
        $this->assertEquals(3990, $product->highest_price); // Unchanged
    }

    public function test_crawl_updates_highest_price_when_price_increases(): void
    {
        $product = Product::factory()->create([
            'product_id' => 'E123456',
            'price_group' => '000',
            'brand' => 'uniqlo',
            'current_price' => 2990,
            'lowest_price' => 2490,
            'highest_price' => 3990,
        ]);

        Http::fake([
            'www.uniqlo.com/*' => Http::response([
                'result' => [
                    'items' => [
                        [
                            'productId' => 'E123456',
                            'priceGroup' => '000',
                            'name' => 'Test Product',
                            'prices' => ['base' => ['value' => 4990]], // Higher than highest
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

        $this->crawler->crawl('uniqlo');

        $product->refresh();
        $this->assertEquals(4990, $product->current_price);
        $this->assertEquals(2490, $product->lowest_price); // Unchanged
        $this->assertEquals(4990, $product->highest_price); // Updated
    }

    public function test_crawl_handles_api_error_gracefully(): void
    {
        Http::fake([
            'www.uniqlo.com/*' => Http::response(null, 500),
            'www.gu-global.com/*' => Http::response([
                'result' => [
                    'items' => [
                        [
                            'productId' => 'E123456',
                            'priceGroup' => '000',
                            'name' => 'GU Product',
                            'prices' => ['base' => ['value' => 990]],
                            'genderCategory' => 'WOMEN',
                            'images' => ['main' => null],
                        ],
                    ],
                    'pagination' => ['total' => 1],
                ],
            ], 200),
        ]);

        $results = $this->crawler->crawl();

        // GU should still succeed
        $this->assertEquals(1, $results['total']);
        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseHas('products', ['brand' => 'gu']);
    }
}
