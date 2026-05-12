<?php

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
