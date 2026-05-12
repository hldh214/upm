<?php

namespace Tests\Feature;

use App\Models\NewProductNotificationSetting;
use App\Models\NotificationSetting;
use App\Models\Product;
use App\Models\User;
use App\Models\Watchlist;
use App\Notifications\PriceAlertNotification;
use App\Services\NotificationService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AuthAndWatchlistTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_with_email_and_password_only(): void
    {
        $response = $this->post('/register', [
            'email' => 'user@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect('/mypage');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'user@example.com',
            'name' => 'user',
        ]);
    }

    public function test_user_can_login_with_email_and_password(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->post('/login', [
            'email' => 'user@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/mypage');
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_can_request_a_password_reset_link(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'user@example.com']);

        $this->post('/forgot-password', ['email' => 'user@example.com'])
            ->assertRedirect();

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_password_reset_page_receives_email_from_reset_link_query(): void
    {
        $this->get('/reset-password/example-token?email=user%40example.com')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/ResetPassword')
                ->where('token', 'example-token')
                ->where('email', 'user@example.com')
            );
    }

    public function test_user_can_reset_password(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('old-password'),
        ]);
        $token = Password::createToken($user);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => 'user@example.com',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertRedirect('/mypage');

        $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
    }

    public function test_authenticated_user_can_manage_watchlist_over_web_session_api(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/watchlist', ['product_id' => $product->id])
            ->assertCreated()
            ->assertJsonPath('product.id', $product->id);

        $this->actingAs($user)
            ->getJson("/api/watchlist/check/{$product->id}")
            ->assertOk()
            ->assertJsonPath('inWatchlist', true);

        $this->actingAs($user)
            ->deleteJson("/api/watchlist/{$product->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('watchlists', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_user_cannot_update_another_users_watchlist_notification_settings(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $product = Product::factory()->create();
        $watchlist = Watchlist::create([
            'user_id' => $owner->id,
            'product_id' => $product->id,
        ]);

        $this->actingAs($otherUser)
            ->putJson("/api/notifications/settings/{$watchlist->id}", [
                'price_drop_enabled' => true,
                'price_drop_target' => 990,
            ])
            ->assertNotFound();
    }

    public function test_user_can_update_own_watchlist_notification_settings(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $watchlist = Watchlist::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        $this->actingAs($user)
            ->putJson("/api/notifications/settings/{$watchlist->id}", [
                'price_drop_enabled' => true,
                'price_drop_target' => 990,
                'price_change_enabled' => true,
                'price_change_min_amount' => 500,
            ])
            ->assertOk()
            ->assertJsonPath('price_drop_enabled', true)
            ->assertJsonPath('price_drop_target', 990)
            ->assertJsonPath('price_change_enabled', true)
            ->assertJsonPath('price_change_min_amount', 500);

        $this->assertDatabaseHas('notification_settings', [
            'user_id' => $user->id,
            'watchlist_id' => $watchlist->id,
            'price_drop_enabled' => true,
            'price_drop_target' => 990,
            'price_change_enabled' => true,
            'price_change_min_amount' => 500,
        ]);
    }

    public function test_user_can_update_new_product_notification_switches(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->putJson('/api/notifications/new-product', [
                'brand' => '*',
                'gender' => '*',
                'enabled' => true,
            ])
            ->assertOk()
            ->assertJsonPath('brand', '*')
            ->assertJsonPath('gender', '*')
            ->assertJsonPath('enabled', true);

        $this->actingAs($user)
            ->putJson('/api/notifications/new-product', [
                'brand' => 'uniqlo',
                'gender' => 'MEN',
                'enabled' => true,
            ])
            ->assertOk()
            ->assertJsonPath('brand', 'uniqlo')
            ->assertJsonPath('gender', 'MEN')
            ->assertJsonPath('enabled', true);

        $this->assertDatabaseHas('new_product_notification_settings', [
            'user_id' => $user->id,
            'brand' => '*',
            'gender' => '*',
            'enabled' => true,
        ]);
        $this->assertDatabaseHas('new_product_notification_settings', [
            'user_id' => $user->id,
            'brand' => 'uniqlo',
            'gender' => 'MEN',
            'enabled' => true,
        ]);
    }

    public function test_mypage_requires_authentication(): void
    {
        $this->get('/mypage')->assertRedirect('/login');

        $this->actingAs(User::factory()->create())
            ->get('/mypage')
            ->assertOk();
    }

    public function test_price_drop_notification_is_recorded_once_and_sent(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $product = Product::factory()->create(['current_price' => 990]);
        $watchlist = Watchlist::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
        NotificationSetting::create([
            'user_id' => $user->id,
            'watchlist_id' => $watchlist->id,
            'price_drop_enabled' => true,
            'price_drop_target' => 1000,
        ]);

        $service = new NotificationService;
        $service->checkPriceDropNotifications($product);
        $service->checkPriceDropNotifications($product);

        $this->assertDatabaseCount('price_notifications', 1);
        $this->assertDatabaseHas('price_notifications', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'notification_type' => 'price_drop',
            'price_at_notification' => 990,
        ]);
        Notification::assertSentTo($user, PriceAlertNotification::class);
    }

    public function test_price_change_notification_is_recorded_and_sent_when_threshold_matches(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $product = Product::factory()->create(['current_price' => 1490]);
        $watchlist = Watchlist::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
        NotificationSetting::create([
            'user_id' => $user->id,
            'watchlist_id' => $watchlist->id,
            'price_change_enabled' => true,
            'price_change_min_amount' => 500,
        ]);

        (new NotificationService)->checkPriceChangeNotifications($product, 1990);

        $this->assertDatabaseHas('price_notifications', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'notification_type' => 'price_change',
            'price_at_notification' => 1490,
        ]);
        Notification::assertSentTo($user, PriceAlertNotification::class);
    }

    public function test_new_product_notification_requires_global_and_category_switches(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $product = Product::factory()->uniqlo()->gender('MEN')->create(['current_price' => 1990]);
        NewProductNotificationSetting::create([
            'user_id' => $user->id,
            'brand' => 'uniqlo',
            'gender' => 'MEN',
            'enabled' => true,
        ]);

        $service = new NotificationService;
        $service->checkNewProductNotifications($product);

        $this->assertDatabaseCount('price_notifications', 0);
        Notification::assertNothingSent();

        NewProductNotificationSetting::create([
            'user_id' => $user->id,
            'brand' => '*',
            'gender' => '*',
            'enabled' => true,
        ]);

        $service->checkNewProductNotifications($product);

        $this->assertDatabaseHas('price_notifications', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'notification_type' => 'new_product',
        ]);
        Notification::assertSentTo($user, PriceAlertNotification::class);
    }
}
