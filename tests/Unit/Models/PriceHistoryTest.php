<?php

namespace Tests\Unit\Models;

use App\Models\Product;
use App\Models\PriceHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_price_history(): void
    {
        $product = Product::factory()->create();

        $priceHistory = PriceHistory::create([
            'product_id' => $product->id,
            'price' => 1990,
        ]);

        $this->assertDatabaseHas('price_histories', [
            'product_id' => $product->id,
            'price' => 1990,
        ]);
    }

    public function test_price_history_belongs_to_product(): void
    {
        $product = Product::factory()->create();

        $priceHistory = PriceHistory::create([
            'product_id' => $product->id,
            'price' => 1990,
        ]);

        $this->assertInstanceOf(Product::class, $priceHistory->product);
        $this->assertEquals($product->id, $priceHistory->product->id);
    }

    public function test_created_at_is_cast_to_datetime(): void
    {
        $product = Product::factory()->create();

        $priceHistory = PriceHistory::create([
            'product_id' => $product->id,
            'price' => 1990,
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $priceHistory->created_at);
    }

    public function test_price_is_cast_to_integer(): void
    {
        $product = Product::factory()->create();

        $priceHistory = PriceHistory::create([
            'product_id' => $product->id,
            'price' => '1990',
        ]);

        $this->assertIsInt($priceHistory->price);
    }

    public function test_allows_multiple_records_per_day(): void
    {
        $product = Product::factory()->create();

        $priceHistory1 = PriceHistory::create([
            'product_id' => $product->id,
            'price' => 1990,
        ]);

        $priceHistory2 = PriceHistory::create([
            'product_id' => $product->id,
            'price' => 2990,
        ]);

        $this->assertDatabaseCount('price_histories', 2);
        $this->assertEquals(1990, $priceHistory1->price);
        $this->assertEquals(2990, $priceHistory2->price);
    }
}
