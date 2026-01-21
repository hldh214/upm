<?php

namespace Tests\Unit\Models;

use App\Models\Product;
use App\Models\PriceHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_product(): void
    {
        $product = Product::create([
            'product_id' => 'E123456',
            'price_group' => '000',
            'name' => 'Test Product',
            'brand' => 'uniqlo',
            'gender' => 'MEN',
            'image_url' => 'https://example.com/image.jpg',
            'current_price' => 1990,
            'lowest_price' => 1490,
            'highest_price' => 2990,
        ]);

        $this->assertDatabaseHas('products', [
            'product_id' => 'E123456',
            'name' => 'Test Product',
            'brand' => 'uniqlo',
        ]);
    }

    public function test_product_has_price_histories_relationship(): void
    {
        $product = Product::factory()->create();

        PriceHistory::create([
            'product_id' => $product->id,
            'price' => 1990,
        ]);

        $this->assertCount(1, $product->priceHistories);
        $this->assertInstanceOf(PriceHistory::class, $product->priceHistories->first());
    }

    public function test_url_attribute_returns_uniqlo_url(): void
    {
        $product = Product::factory()->create([
            'product_id' => 'E123456',
            'price_group' => '000',
            'brand' => 'uniqlo',
        ]);

        $this->assertEquals(
            'https://www.uniqlo.com/jp/ja/products/E123456/000',
            $product->url
        );
    }

    public function test_url_attribute_returns_gu_url(): void
    {
        $product = Product::factory()->create([
            'product_id' => 'E789012',
            'price_group' => '001',
            'brand' => 'gu',
        ]);

        $this->assertEquals(
            'https://www.gu-global.com/jp/ja/products/E789012/001',
            $product->url
        );
    }

    public function test_search_scope_filters_by_name(): void
    {
        Product::factory()->create(['name' => 'Blue T-Shirt']);
        Product::factory()->create(['name' => 'Red Pants']);
        Product::factory()->create(['name' => 'Blue Jacket']);

        $results = Product::search('Blue')->get();

        $this->assertCount(2, $results);
    }

    public function test_search_scope_filters_by_product_id(): void
    {
        Product::factory()->create(['product_id' => 'E123456']);
        Product::factory()->create(['product_id' => 'E789012']);

        $results = Product::search('E123')->get();

        $this->assertCount(1, $results);
    }

    public function test_search_scope_returns_all_when_keyword_is_null(): void
    {
        Product::factory()->count(3)->create();

        $results = Product::search(null)->get();

        $this->assertCount(3, $results);
    }

    public function test_brand_scope_filters_by_brand(): void
    {
        Product::factory()->create(['brand' => 'uniqlo']);
        Product::factory()->create(['brand' => 'uniqlo']);
        Product::factory()->create(['brand' => 'gu']);

        $uniqloResults = Product::brand('uniqlo')->get();
        $guResults = Product::brand('gu')->get();

        $this->assertCount(2, $uniqloResults);
        $this->assertCount(1, $guResults);
    }

    public function test_brand_scope_returns_all_when_brand_is_null(): void
    {
        Product::factory()->count(3)->create();

        $results = Product::brand(null)->get();

        $this->assertCount(3, $results);
    }

    public function test_gender_scope_filters_by_gender(): void
    {
        Product::factory()->create(['gender' => 'MEN']);
        Product::factory()->create(['gender' => 'WOMEN']);
        Product::factory()->create(['gender' => 'MEN']);

        $results = Product::gender('MEN')->get();

        $this->assertCount(2, $results);
    }

    public function test_gender_scope_returns_all_when_gender_is_null(): void
    {
        Product::factory()->count(3)->create();

        $results = Product::gender(null)->get();

        $this->assertCount(3, $results);
    }

    public function test_unique_constraint_on_product_id_and_price_group(): void
    {
        Product::factory()->create([
            'product_id' => 'E123456',
            'price_group' => '000',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Product::factory()->create([
            'product_id' => 'E123456',
            'price_group' => '000',
        ]);
    }
}
