<?php

use App\Http\Controllers\Api\NotificationSettingController;
use App\Http\Controllers\Api\WatchlistController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [PageController::class, 'index'])->name('home');
Route::get('/products/{id}', [PageController::class, 'show'])->name('products.show');

// Authentication pages (for unauthenticated users)
Route::middleware('guest')->group(function () {
    Route::get('/login', function () {
        return Inertia::render('Auth/Login');
    })->name('login');

    Route::get('/register', function () {
        return Inertia::render('Auth/Register');
    })->name('register');

    Route::get('/forgot-password', function () {
        return Inertia::render('Auth/ForgotPassword');
    })->name('password.request');

    Route::get('/reset-password/{token}', function (Illuminate\Http\Request $request, string $token) {
        return Inertia::render('Auth/ResetPassword', [
            'email' => $request->query('email'),
            'token' => $token,
        ]);
    })->name('password.reset');
});

// MyPage (requires authentication)
Route::middleware('auth')->group(function () {
    Route::get('/mypage', function () {
        return Inertia::render('MyPage/Index');
    })->name('mypage');

    Route::prefix('api')->group(function () {
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
});
