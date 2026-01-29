<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NotificationSetting;
use App\Models\NewProductNotificationSetting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NotificationSettingController extends Controller
{
    /**
     * Get notification settings for a watchlist item.
     */
    public function show(Request $request, int $watchlistId): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $settings = NotificationSetting::where('user_id', $user->id)
            ->where('watchlist_id', $watchlistId)
            ->first();

        if (!$settings) {
            // Create default settings if not exists
            $settings = NotificationSetting::create([
                'user_id' => $user->id,
                'watchlist_id' => $watchlistId,
            ]);
        }

        return response()->json($settings);
    }

    /**
     * Update notification settings for a watchlist item.
     */
    public function update(Request $request, int $watchlistId): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $validated = $request->validate([
            'price_drop_enabled' => 'boolean',
            'price_drop_target' => 'nullable|integer|min:0',
            'price_change_enabled' => 'boolean',
            'price_change_min_amount' => 'nullable|integer|min:0',
            'new_product_enabled' => 'boolean',
        ]);

        $settings = NotificationSetting::where('user_id', $user->id)
            ->where('watchlist_id', $watchlistId)
            ->first();

        if (!$settings) {
            $settings = new NotificationSetting([
                'user_id' => $user->id,
                'watchlist_id' => $watchlistId,
            ]);
        }

        $settings->fill($validated)->save();

        return response()->json($settings);
    }

    /**
     * Get new product notification settings.
     */
    public function newProductSettings(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $settings = NewProductNotificationSetting::where('user_id', $user->id)->get();

        return response()->json($settings);
    }

    /**
     * Update new product notification settings.
     */
    public function updateNewProductSettings(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $validated = $request->validate([
            'brand' => 'required|string',
            'gender' => 'required|string',
            'enabled' => 'required|boolean',
        ]);

        $settings = NewProductNotificationSetting::where('user_id', $user->id)
            ->where('brand', $validated['brand'])
            ->where('gender', $validated['gender'])
            ->first();

        if (!$settings) {
            $settings = NewProductNotificationSetting::create([
                'user_id' => $user->id,
                'brand' => $validated['brand'],
                'gender' => $validated['gender'],
                'enabled' => $validated['enabled'],
            ]);
        } else {
            $settings->update(['enabled' => $validated['enabled']]);
        }

        return response()->json($settings);
    }
}
