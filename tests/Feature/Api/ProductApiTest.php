<?php

namespace Tests\Feature\Api;

use App\Models\Product;
use App\Models\PriceHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_paginated_products(): void
    {
        Product::factory()->count(25)->create();

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'product_id',
                        'price_group',
                        'name',
                        'brand',
                        'gender',
                        'image_url',
                        'current_price',
                        'lowest_price',
                        'highest_price',
                    ],
                ],
                'current_page',
                'last_page',
                'per_page',
                'total',
            ])
            ->assertJsonCount(20, 'data'); // Default per_page is 20
    }

    public function test_index_respects_per_page_parameter(): void
    {
        Product::factory()->count(15)->create();

        $response = $this->getJson('/api/products?per_page=10');

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data');
    }

    public function test_index_limits_per_page_to_100(): void
    {
        Product::factory()->count(150)->create();

        $response = $this->getJson('/api/products?per_page=200');

        $response->assertStatus(200)
            ->assertJsonCount(100, 'data');
    }

    public function test_index_filters_by_search_keyword(): void
    {
        Product::factory()->create(['name' => 'Blue T-Shirt']);
        Product::factory()->create(['name' => 'Red Pants']);
        Product::factory()->create(['name' => 'Blue Jacket']);

        $response = $this->getJson('/api/products?q=Blue');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_index_filters_by_brand(): void
    {
        Product::factory()->count(3)->uniqlo()->create();
        Product::factory()->count(2)->gu()->create();

        $response = $this->getJson('/api/products?brand=uniqlo');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');

        foreach ($response->json('data') as $product) {
            $this->assertEquals('uniqlo', $product['brand']);
        }
    }

    public function test_index_filters_by_gender(): void
    {
        Product::factory()->count(3)->gender('MEN')->create();
        Product::factory()->count(2)->gender('WOMEN')->create();

        $response = $this->getJson('/api/products?gender=WOMEN');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');

        foreach ($response->json('data') as $product) {
            $this->assertEquals('WOMEN', $product['gender']);
        }
    }

    public function test_index_sorts_by_price_ascending(): void
    {
        Product::factory()->create(['current_price' => 2990]);
        Product::factory()->create(['current_price' => 990]);
        Product::factory()->create(['current_price' => 1990]);

        $response = $this->getJson('/api/products?sort=price_asc');

        $response->assertStatus(200);

        $prices = collect($response->json('data'))->pluck('current_price')->toArray();
        $this->assertEquals([990, 1990, 2990], $prices);
    }

    public function test_index_sorts_by_price_descending(): void
    {
        Product::factory()->create(['current_price' => 2990]);
        Product::factory()->create(['current_price' => 990]);
        Product::factory()->create(['current_price' => 1990]);

        $response = $this->getJson('/api/products?sort=price_desc');

        $response->assertStatus(200);

        $prices = collect($response->json('data'))->pluck('current_price')->toArray();
        $this->assertEquals([2990, 1990, 990], $prices);
    }

    public function test_index_supports_pagination(): void
    {
        Product::factory()->count(25)->create();

        $response = $this->getJson('/api/products?page=2&per_page=10');

        $response->assertStatus(200)
            ->assertJson([
                'current_page' => 2,
                'per_page' => 10,
            ])
            ->assertJsonCount(10, 'data');
    }

    public function test_show_returns_product_with_price_histories(): void
    {
        $product = Product::factory()->create();

        // Create price histories
        for ($i = 0; $i < 5; $i++) {
            PriceHistory::create([
                'product_id' => $product->id,
                'price' => 1990 + ($i * 100),
            ]);
        }

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'product' => [
                    'id',
                    'product_id',
                    'price_group',
                    'name',
                    'brand',
                    'price_histories' => [
                        '*' => ['id', 'price', 'created_at'],
                    ],
                ],
                'url',
            ]);
    }

    public function test_show_returns_correct_url_for_uniqlo(): void
    {
        $product = Product::factory()->uniqlo()->create([
            'product_id' => 'E123456',
            'price_group' => '000',
        ]);

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJson([
                'url' => 'https://www.uniqlo.com/jp/ja/products/E123456/000',
            ]);
    }

    public function test_show_returns_correct_url_for_gu(): void
    {
        $product = Product::factory()->gu()->create([
            'product_id' => 'E789012',
            'price_group' => '001',
        ]);

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJson([
                'url' => 'https://www.gu-global.com/jp/ja/products/E789012/001',
            ]);
    }

    public function test_show_returns_404_for_non_existent_product(): void
    {
        $response = $this->getJson('/api/products/99999');

        $response->assertStatus(404);
    }

    public function test_price_history_returns_history_for_product(): void
    {
        $product = Product::factory()->create([
            'current_price' => 1990,
            'lowest_price' => 990,
            'highest_price' => 2990,
        ]);

        // Create price histories at different times
        $this->travel(-30)->days();
        PriceHistory::create([
            'product_id' => $product->id,
            'price' => 2990,
        ]);

        $this->travel(15)->days();
        PriceHistory::create([
            'product_id' => $product->id,
            'price' => 1990,
        ]);

        $this->travelBack();
        PriceHistory::create([
            'product_id' => $product->id,
            'price' => 990,
        ]);

        $response = $this->getJson("/api/products/{$product->id}/history");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'product_id',
                'name',
                'current_price',
                'lowest_price',
                'highest_price',
                'history' => [
                    '*' => ['date', 'price'],
                ],
            ])
            ->assertJsonCount(3, 'history');
    }

    public function test_price_history_respects_days_parameter(): void
    {
        $product = Product::factory()->create();

        // Create history for 100 days
        for ($i = 0; $i < 100; $i++) {
            $this->travel(-$i)->days();
            PriceHistory::create([
                'product_id' => $product->id,
                'price' => 1990,
            ]);
            $this->travelBack();
        }

        $response = $this->getJson("/api/products/{$product->id}/history?days=30");

        $response->assertStatus(200);
        // Should return records from last 30 days
        $this->assertLessThanOrEqual(31, count($response->json('history')));
    }

    public function test_price_history_limits_days_to_365(): void
    {
        $product = Product::factory()->create();

        // Create history for 400 days
        for ($i = 0; $i < 400; $i++) {
            $this->travel(-$i)->days();
            PriceHistory::create([
                'product_id' => $product->id,
                'price' => 1990,
            ]);
            $this->travelBack();
        }

        $response = $this->getJson("/api/products/{$product->id}/history?days=500");

        $response->assertStatus(200);

        // Should only return max 366 records (365 days + today)
        $this->assertLessThanOrEqual(366, count($response->json('history')));
    }

    public function test_price_history_returns_history_in_chronological_order(): void
    {
        $product = Product::factory()->create();

        $this->travel(-2)->days();
        PriceHistory::create([
            'product_id' => $product->id,
            'price' => 1990,
        ]);

        $this->travelBack();
        PriceHistory::create([
            'product_id' => $product->id,
            'price' => 2990,
        ]);

        $this->travel(-1)->days();
        PriceHistory::create([
            'product_id' => $product->id,
            'price' => 2490,
        ]);
        $this->travelBack();

        $response = $this->getJson("/api/products/{$product->id}/history");

        $response->assertStatus(200);

        $dates = collect($response->json('history'))->pluck('date')->toArray();
        $sortedDates = $dates;
        sort($sortedDates);

        $this->assertEquals($sortedDates, $dates);
    }

    public function test_stats_returns_correct_statistics(): void
    {
        Product::factory()->count(5)->uniqlo()->create();
        Product::factory()->count(3)->gu()->create();

        Product::factory()->uniqlo()->gender('MEN')->create();
        Product::factory()->uniqlo()->gender('WOMEN')->create();

        $response = $this->getJson('/api/products/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_products',
                'uniqlo_count',
                'gu_count',
                'genders',
            ])
            ->assertJson([
                'total_products' => 10,
                'uniqlo_count' => 7,
                'gu_count' => 3,
            ]);
    }

    public function test_stats_returns_distinct_genders(): void
    {
        Product::factory()->gender('MEN')->create();
        Product::factory()->gender('MEN')->create();
        Product::factory()->gender('WOMEN')->create();
        Product::factory()->create(['gender' => null]);

        $response = $this->getJson('/api/products/stats');

        $response->assertStatus(200);

        $genders = $response->json('genders');
        $this->assertCount(2, $genders);
        $this->assertContains('MEN', $genders);
        $this->assertContains('WOMEN', $genders);
    }

    public function test_index_combines_multiple_filters(): void
    {
        // UNIQLO MEN products
        Product::factory()->count(3)->uniqlo()->gender('MEN')->create(['name' => 'Shirt MEN']);
        // UNIQLO WOMEN products
        Product::factory()->count(2)->uniqlo()->gender('WOMEN')->create(['name' => 'Shirt WOMEN']);
        // GU MEN products
        Product::factory()->count(2)->gu()->gender('MEN')->create(['name' => 'Pants MEN']);

        $response = $this->getJson('/api/products?brand=uniqlo&gender=MEN&q=Shirt');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');

        foreach ($response->json('data') as $product) {
            $this->assertEquals('uniqlo', $product['brand']);
            $this->assertEquals('MEN', $product['gender']);
            $this->assertStringContainsString('Shirt', $product['name']);
        }
    }
}
