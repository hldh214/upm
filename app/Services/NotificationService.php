<?php

namespace App\Services;

use App\Models\Watchlist;
use App\Models\NotificationSetting;
use App\Models\PriceNotification;
use App\Models\NewProductNotificationSetting;
use App\Models\Product;
use App\Models\User;

class NotificationService
{
    /**
     * Check and send price drop notifications
     */
    public function checkPriceDropNotifications(Product $product): void
    {
        // Get all notification settings for this product with price_drop_enabled
        $settings = NotificationSetting::where('watchlist_id', function ($query) use ($product) {
            $query->select('id')
                ->from('watchlists')
                ->where('product_id', $product->id);
        })
            ->where('price_drop_enabled', true)
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
        $settings = NotificationSetting::where('watchlist_id', function ($query) use ($product) {
            $query->select('id')
                ->from('watchlists')
                ->where('product_id', $product->id);
        })
            ->where('price_change_enabled', true)
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
        $settings = NewProductNotificationSetting::where('brand', $product->brand)
            ->where('gender', $product->gender)
            ->where('enabled', true)
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
        if (!$setting->price_drop_target) {
            return;
        }

        // Check if current price is less than or equal to target
        if ($product->current_price <= $setting->price_drop_target) {
            // Check if notification already sent
            $alreadySent = PriceNotification::where('user_id', $setting->user_id)
                ->where('product_id', $product->id)
                ->where('notification_type', 'price_drop')
                ->exists();

            if (!$alreadySent) {
                // Create notification record
                PriceNotification::create([
                    'user_id' => $setting->user_id,
                    'product_id' => $product->id,
                    'notification_type' => 'price_drop',
                    'price_at_notification' => $product->current_price,
                ]);

                // TODO: Send actual notification (email, push, etc.)
            }
        }
    }

    /**
     * Process price change notification
     */
    private function processPriceChangeNotification(Product $product, int $previousPrice, NotificationSetting $setting): void
    {
        if (!$setting->price_change_min_amount) {
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

            // TODO: Send actual notification (email, push, etc.)
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

        if (!$alreadySent) {
            // Create notification record
            PriceNotification::create([
                'user_id' => $setting->user_id,
                'product_id' => $product->id,
                'notification_type' => 'new_product',
                'price_at_notification' => $product->current_price,
            ]);

            // TODO: Send actual notification (email, push, etc.)
        }
    }

    /**
     * Get pending notifications for a user
     */
    public function getUserNotifications(User $user, int $limit = 20)
    {
        return PriceNotification::where('user_id', $user->id)
            ->with('product')
            ->latest()
            ->limit($limit)
            ->get();
    }
}
