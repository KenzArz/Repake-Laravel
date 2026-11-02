<?php

use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductDetailController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

// Detail Barang & Laporan
Route::get('/products/{id}', [ProductDetailController::class, 'show'])->name('products.show');
Route::post('/products/{id}/report', [ProductDetailController::class, 'report'])->name('products.report');

// Favorit
Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');
Route::post('/favorites/{id}', [FavoriteController::class, 'toggle'])->name('favorites.toggle');

// Profil & Katalog
Route::get('/profile', [ProfileController::class, 'catalog'])->name('profile.catalog');
Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
Route::post('/logout', [ProfileController::class, 'logout'])->name('logout');
