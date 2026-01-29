<?php

use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [PageController::class, 'index'])->name('home');
Route::get('/products/{id}', [PageController::class, 'show'])->name('products.show');

// MyPage (requires authentication)
Route::middleware('auth')->group(function () {
    Route::get('/mypage', function () {
        return Inertia::render('MyPage/Index');
    })->name('mypage');
});
