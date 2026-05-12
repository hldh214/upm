<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NewProductNotificationSetting;
use App\Models\NotificationSetting;
use App\Models\Product;
use App\Models\Watchlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class NotificationSettingController extends Controller
{
    /**
     * Get notification settings for a watchlist item.
     */
    public function show(Request $request, int $watchlistId): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $watchlist = Watchlist::where('user_id', $user->id)->findOrFail($watchlistId);

        $settings = NotificationSetting::where('user_id', $user->id)
            ->where('watchlist_id', $watchlistId)
            ->first();

        if (! $settings) {
            // Create default settings if not exists
            $settings = NotificationSetting::create([
                'user_id' => $user->id,
                'watchlist_id' => $watchlist->id,
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

        if (! $user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $watchlist = Watchlist::where('user_id', $user->id)->findOrFail($watchlistId);

        $validated = $request->validate([
            'price_drop_enabled' => 'boolean',
            'price_drop_target' => 'nullable|integer|min:0',
            'price_change_enabled' => 'boolean',
            'price_change_min_amount' => 'nullable|integer|min:0',
        ]);

        $settings = NotificationSetting::where('user_id', $user->id)
            ->where('watchlist_id', $watchlistId)
            ->first();

        if (! $settings) {
            $settings = new NotificationSetting([
                'user_id' => $user->id,
                'watchlist_id' => $watchlist->id,
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

        if (! $user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $settings = NewProductNotificationSetting::where('user_id', $user->id)->get();

        return response()->json([
            'settings' => $settings,
            'brands' => ['uniqlo', 'gu'],
            'genders' => Product::AVAILABLE_GENDERS,
            'global' => $settings
                ->where('brand', '*')
                ->where('gender', '*')
                ->first(),
        ]);
    }

    /**
     * Update new product notification settings.
     */
    public function updateNewProductSettings(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $validated = $request->validate([
            'brand' => ['required', 'string', Rule::in(['*', 'uniqlo', 'gu'])],
            'gender' => ['required', 'string', Rule::in(['*', ...Product::AVAILABLE_GENDERS])],
            'enabled' => 'required|boolean',
        ]);

        if (($validated['brand'] === '*') !== ($validated['gender'] === '*')) {
            throw ValidationException::withMessages([
                'brand' => 'The global switch must use * for both brand and gender.',
            ]);
        }

        $settings = NewProductNotificationSetting::where('user_id', $user->id)
            ->where('brand', $validated['brand'])
            ->where('gender', $validated['gender'])
            ->first();

        if (! $settings) {
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
