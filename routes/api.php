<?php

use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::get('/stats', [ProductController::class, 'stats']);
    Route::get('/price-dropped', [ProductController::class, 'priceDropped']);
    Route::get('/{id}', [ProductController::class, 'show']);
    Route::get('/{id}/history', [ProductController::class, 'priceHistory']);
});
