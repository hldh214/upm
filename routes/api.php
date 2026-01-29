<?php

use App\Http\Controllers\Api\NotificationSettingController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\WatchlistController;
use Illuminate\Support\Facades\Route;

Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::get('/stats', [ProductController::class, 'stats']);
    Route::get('/{id}/watchlist-count', [WatchlistController::class, 'count']);
    Route::get('/{id}', [ProductController::class, 'show']);
    Route::get('/{id}/history', [ProductController::class, 'priceHistory']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('watchlist')->group(function () {
        Route::get('/', [WatchlistController::class, 'index']);
        Route::post('/', [WatchlistController::class, 'store']);
        Route::get('check/{productId}', [WatchlistController::class, 'check']);
        Route::delete('{productId}', [WatchlistController::class, 'destroy']);
    });

    Route::prefix('notifications')->group(function () {
        Route::get('settings/{watchlistId}', [NotificationSettingController::class, 'show']);
        Route::put('settings/{watchlistId}', [NotificationSettingController::class, 'update']);
        Route::get('new-product', [NotificationSettingController::class, 'newProductSettings']);
        Route::put('new-product', [NotificationSettingController::class, 'updateNewProductSettings']);
    });
});
