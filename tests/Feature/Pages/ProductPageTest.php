<?php

namespace Tests\Feature\Pages;

use App\Models\Product;
use App\Models\PriceHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_page_loads_successfully(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200)
            ->assertViewIs('products.index');
    }

    public function test_index_page_contains_required_elements(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200)
            ->assertSee('UPM')
            ->assertSee('Search')
            ->assertSee('Brand')
            ->assertSee('Gender');
    }

    public function test_show_page_loads_successfully(): void
    {
        $product = Product::factory()->create();

        $response = $this->get("/products/{$product->id}");

        $response->assertStatus(200)
            ->assertViewIs('products.show')
            ->assertViewHas('product');
    }

    public function test_show_page_displays_product_information(): void
    {
        $product = Product::factory()->create([
            'name' => 'Test Product Name',
            'brand' => 'uniqlo',
            'current_price' => 1990,
            'lowest_price' => 990,
            'highest_price' => 2990,
        ]);

        $response = $this->get("/products/{$product->id}");

        $response->assertStatus(200)
            ->assertSee('Test Product Name')
            ->assertSee('UNIQLO')
            ->assertSee('1,990')
            ->assertSee('990')
            ->assertSee('2,990');
    }

    public function test_show_page_displays_lowest_price_indicator(): void
    {
        $product = Product::factory()->create([
            'current_price' => 990,
            'lowest_price' => 990,
            'highest_price' => 2990,
        ]);

        $response = $this->get("/products/{$product->id}");

        $response->assertStatus(200)
            ->assertSee('Now at the lowest price!');
    }

    public function test_show_page_has_link_to_official_site(): void
    {
        $product = Product::factory()->uniqlo()->create([
            'product_id' => 'E123456',
            'price_group' => '000',
        ]);

        $response = $this->get("/products/{$product->id}");

        $response->assertStatus(200)
            ->assertSee('View on Official Site')
            ->assertSee('https://www.uniqlo.com/jp/ja/products/E123456/000');
    }

    public function test_show_page_has_back_to_list_link(): void
    {
        $product = Product::factory()->create();

        $response = $this->get("/products/{$product->id}");

        $response->assertStatus(200)
            ->assertSee('Back to list');
    }

    public function test_show_page_returns_404_for_non_existent_product(): void
    {
        $response = $this->get('/products/99999');

        $response->assertStatus(404);
    }

    public function test_show_page_loads_product_with_price_histories(): void
    {
        $product = Product::factory()->create();

        PriceHistory::factory()->count(10)->create([
            'product_id' => $product->id,
        ]);

        $response = $this->get("/products/{$product->id}");

        $response->assertStatus(200);

        $viewProduct = $response->viewData('product');
        $this->assertTrue($viewProduct->relationLoaded('priceHistories'));
        $this->assertCount(10, $viewProduct->priceHistories);
    }

    public function test_show_page_displays_product_id_and_price_group(): void
    {
        $product = Product::factory()->create([
            'product_id' => 'E123456',
            'price_group' => '001',
        ]);

        $response = $this->get("/products/{$product->id}");

        $response->assertStatus(200)
            ->assertSee('E123456')
            ->assertSee('001');
    }

    public function test_show_page_displays_gender_badge(): void
    {
        $product = Product::factory()->create([
            'gender' => 'WOMEN',
        ]);

        $response = $this->get("/products/{$product->id}");

        $response->assertStatus(200)
            ->assertSee('WOMEN');
    }

    public function test_index_page_has_correct_route_name(): void
    {
        $this->assertEquals(url('/'), route('home'));
    }

    public function test_show_page_has_correct_route_name(): void
    {
        $product = Product::factory()->create();

        $this->assertEquals(
            url("/products/{$product->id}"),
            route('products.show', $product->id)
        );
    }
}
