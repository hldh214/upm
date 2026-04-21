<?php

namespace Tests\Feature\Pages;

use App\Models\PriceHistory;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProductPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_page_loads_successfully(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Products/Index')
                ->has('products')
                ->has('stats')
                ->where('availableGenders', Product::AVAILABLE_GENDERS)
                ->has('filters')
            );
    }

    public function test_index_page_contains_required_elements(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Products/Index')
                ->where('translations.search', __('ui.search'))
                ->where('translations.brand', __('ui.brand'))
                ->where('translations.gender', __('ui.gender'))
                ->where('translations.tagline', __('ui.tagline'))
            );
    }

    public function test_index_page_includes_unisex_gender_filter_option(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200)
            ->assertSee('UNISEX')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Products/Index')
                ->where('availableGenders', Product::AVAILABLE_GENDERS)
            );
    }

    public function test_index_page_price_change_filter_uses_latest_change_within_period(): void
    {
        $matchingProduct = Product::factory()->uniqlo()->gender('UNISEX')->create();
        $mixedDirectionProduct = Product::factory()->uniqlo()->gender('UNISEX')->create();

        $this->travel(-20)->days();
        PriceHistory::create([
            'product_id' => $matchingProduct->id,
            'price' => 1990,
        ]);
        PriceHistory::create([
            'product_id' => $mixedDirectionProduct->id,
            'price' => 2990,
        ]);

        $this->travel(10)->days();
        PriceHistory::create([
            'product_id' => $matchingProduct->id,
            'price' => 1490,
        ]);
        PriceHistory::create([
            'product_id' => $mixedDirectionProduct->id,
            'price' => 2490,
        ]);

        $this->travel(9)->days();
        PriceHistory::create([
            'product_id' => $mixedDirectionProduct->id,
            'price' => 2690,
        ]);
        $this->travelBack();

        $response = $this->get('/?brand=uniqlo&gender=UNISEX&price_change=dropped&change_days=14');

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Products/Index')
                ->has('products.data', 1)
                ->where('products.data.0.id', $matchingProduct->id)
                ->where('products.data.0.price_change_type', 'dropped')
            );
    }

    public function test_show_page_loads_successfully(): void
    {
        $product = Product::factory()->create();

        $response = $this->get("/products/{$product->id}");

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Products/Show')
                ->has('product', fn (Assert $productPage) => $productPage
                    ->where('id', $product->id)
                    ->etc()
                )
                ->has('history')
                ->has('watchlistCount')
            );
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
            ->assertInertia(fn (Assert $page) => $page
                ->component('Products/Show')
                ->has('product', fn (Assert $productPage) => $productPage
                    ->where('name', 'Test Product Name')
                    ->where('brand', 'uniqlo')
                    ->where('current_price', 1990)
                    ->where('lowest_price', 990)
                    ->where('highest_price', 2990)
                    ->etc()
                )
            );
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
            ->assertInertia(fn (Assert $page) => $page
                ->component('Products/Show')
                ->where('product.current_price', 990)
                ->where('product.lowest_price', 990)
                ->where('translations.lowest_now', __('ui.lowest_now'))
            );
    }

    public function test_show_page_has_link_to_official_site(): void
    {
        $product = Product::factory()->uniqlo()->create([
            'product_id' => 'E123456',
            'price_group' => '000',
        ]);

        $response = $this->get("/products/{$product->id}");

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Products/Show')
                ->where('translations.view_official', __('ui.view_official'))
                ->has('product', fn (Assert $productPage) => $productPage
                    ->where('brand', 'uniqlo')
                    ->where('product_id', 'E123456')
                    ->where('price_group', '000')
                    ->etc()
                )
            );
    }

    public function test_show_page_has_back_to_list_link(): void
    {
        $product = Product::factory()->create();

        $response = $this->get("/products/{$product->id}");

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Products/Show')
                ->where('translations.back_to_list', __('ui.back_to_list'))
            );
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

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Products/Show')
                ->where('product.id', $product->id)
                ->has('product.price_histories', 10)
                ->has('history', 10)
            );
    }

    public function test_show_page_displays_product_id_and_price_group(): void
    {
        $product = Product::factory()->create([
            'product_id' => 'E123456',
            'price_group' => '001',
        ]);

        $response = $this->get("/products/{$product->id}");

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Products/Show')
                ->where('product.product_id', 'E123456')
                ->where('product.price_group', '001')
            );
    }

    public function test_show_page_displays_gender_badge(): void
    {
        $product = Product::factory()->create([
            'gender' => 'WOMEN',
        ]);

        $response = $this->get("/products/{$product->id}");

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Products/Show')
                ->where('product.gender', 'WOMEN')
            );
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
