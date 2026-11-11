<?php

use App\Http\Controllers\Auth\FirebaseAuthController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductDetailController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

// Otentikasi Firebase Google
Route::post('/auth/firebase', [FirebaseAuthController::class, 'login'])
    ->middleware('throttle:10,1')
    ->name('auth.firebase');

Route::post('/logout', [FirebaseAuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// Rute bertipe Spesifik/Statis (Wajib Ditaruh SEBELUM Wildcard /{id})
Route::middleware('auth')->group(function () {
    Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');

    Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('/favorites/{id}', [FavoriteController::class, 'toggle'])->name('favorites.toggle');

    Route::get('/profile', [ProfileController::class, 'catalog'])->name('profile.catalog');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

// Rute Wildcard /{id} (Ditaruh PALING BAWAH di modul produk)
Route::get('/products/{id}', [ProductDetailController::class, 'show'])->name('products.show');
Route::post('/products/{id}/report', [ProductDetailController::class, 'report'])->name('products.report');
