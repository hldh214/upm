<?php

namespace App\Services;

use App\Models\NewProductNotificationSetting;
use App\Models\NotificationSetting;
use App\Models\PriceNotification;
use App\Models\Product;
use App\Models\User;
use App\Notifications\PriceAlertNotification;

class NotificationService
{
    /**
     * Check and send price drop notifications
     */
    public function checkPriceDropNotifications(Product $product): void
    {
        $settings = NotificationSetting::query()
            ->whereHas('watchlist', function ($query) use ($product) {
                $query->where('product_id', $product->id);
            })
            ->where('price_drop_enabled', true)
            ->with('user')
            ->get();

        foreach ($settings as $setting) {
            $this->processPriceDropNotification($product, $setting);
        }
    }

    /**
     * Check and send price change notifications
     */
    public function checkPriceChangeNotifications(Product $product, int $previousPrice): void
    {
        $settings = NotificationSetting::query()
            ->whereHas('watchlist', function ($query) use ($product) {
                $query->where('product_id', $product->id);
            })
            ->where('price_change_enabled', true)
            ->with('user')
            ->get();

        foreach ($settings as $setting) {
            $this->processPriceChangeNotification($product, $previousPrice, $setting);
        }
    }

    /**
     * Check and send new product notifications
     */
    public function checkNewProductNotifications(Product $product): void
    {
        $settings = NewProductNotificationSetting::query()
            ->where('brand', $product->brand)
            ->where('gender', $product->gender)
            ->where('enabled', true)
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('new_product_notification_settings as global_settings')
                    ->whereColumn('global_settings.user_id', 'new_product_notification_settings.user_id')
                    ->where('global_settings.brand', '*')
                    ->where('global_settings.gender', '*')
                    ->where('global_settings.enabled', true);
            })
            ->with('user')
            ->get();

        foreach ($settings as $setting) {
            $this->processNewProductNotification($product, $setting);
        }
    }

    /**
     * Process price drop notification
     */
    private function processPriceDropNotification(Product $product, NotificationSetting $setting): void
    {
        if (! $setting->price_drop_target) {
            return;
        }

        // Check if current price is less than or equal to target
        if ($product->current_price <= $setting->price_drop_target) {
            // Check if notification already sent
            $alreadySent = PriceNotification::where('user_id', $setting->user_id)
                ->where('product_id', $product->id)
                ->where('notification_type', 'price_drop')
                ->exists();

            if (! $alreadySent) {
                // Create notification record
                PriceNotification::create([
                    'user_id' => $setting->user_id,
                    'product_id' => $product->id,
                    'notification_type' => 'price_drop',
                    'price_at_notification' => $product->current_price,
                ]);

                $setting->user?->notify(new PriceAlertNotification($product, 'price_drop'));
            }
        }
    }

    /**
     * Process price change notification
     */
    private function processPriceChangeNotification(Product $product, int $previousPrice, NotificationSetting $setting): void
    {
        if (! $setting->price_change_min_amount) {
            return;
        }

        $priceChange = abs($product->current_price - $previousPrice);

        if ($priceChange >= $setting->price_change_min_amount) {
            // Create notification record
            PriceNotification::create([
                'user_id' => $setting->user_id,
                'product_id' => $product->id,
                'notification_type' => 'price_change',
                'price_at_notification' => $product->current_price,
            ]);

            $setting->user?->notify(new PriceAlertNotification($product, 'price_change'));
        }
    }

    /**
     * Process new product notification
     */
    private function processNewProductNotification(Product $product, NewProductNotificationSetting $setting): void
    {
        // Check if notification already sent for this user and product
        $alreadySent = PriceNotification::where('user_id', $setting->user_id)
            ->where('product_id', $product->id)
            ->where('notification_type', 'new_product')
            ->exists();

        if (! $alreadySent) {
            // Create notification record
            PriceNotification::create([
                'user_id' => $setting->user_id,
                'product_id' => $product->id,
                'notification_type' => 'new_product',
                'price_at_notification' => $product->current_price,
            ]);

            $setting->user?->notify(new PriceAlertNotification($product, 'new_product'));
        }
    }

    /**
     * Get pending notifications for a user
     */
    public function getUserNotifications(User $user, int $limit = 20)
    {
        return PriceNotification::query()
            ->where('user_id', $user->id)
            ->with('product')
            ->latest()
            ->limit($limit)
            ->get();
    }
}
