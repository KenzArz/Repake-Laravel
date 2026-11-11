<?php

use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Auth\FirebaseAuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\NotificationController;
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

// Jual Produk (wajib login) — rute statis, harus sebelum wildcard /products/{id}
Route::middleware(['auth', 'banned'])->group(function () {
    Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
});

// Favorit (wajib login)
Route::middleware(['auth', 'banned'])->group(function () {
    Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('/favorites/{id}', [FavoriteController::class, 'toggle'])->name('favorites.toggle');
});

// Profil & Katalog (wajib login)
Route::middleware(['auth', 'banned'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'catalog'])->name('profile.catalog');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

// Admin (wajib login + role admin)
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'banned', 'admin'])
    ->group(function () {
        Route::resource('categories', AdminCategoryController::class)
            ->only(['index', 'store', 'update', 'destroy']);
    });

// Chat (wajib login)
Route::middleware(['auth', 'banned'])->group(function () {
    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::post('/chat', [ChatController::class, 'start'])->name('chat.store');
    Route::get('/chat/{conversation}', [ChatController::class, 'show'])
        ->scopeBindings()
        ->name('chat.show');
    Route::get('/chat/{conversation}/messages', [ChatController::class, 'poll'])
        ->name('chat.messages.poll');
    Route::post('/chat/{conversation}/messages', [ChatController::class, 'sendMessage'])
        ->name('chat.messages.store');
    Route::post('/chat/{conversation}/messages/{message}/accept', [ChatController::class, 'acceptOffer'])
        ->scopeBindings()
        ->name('chat.offers.accept');
    Route::post('/chat/{conversation}/messages/{message}/reject', [ChatController::class, 'rejectOffer'])
        ->scopeBindings()
        ->name('chat.offers.reject');
    Route::put('/chat/{conversation}/messages/{message}/offer', [ChatController::class, 'updateOffer'])
        ->scopeBindings()
        ->name('chat.offers.update');
    Route::post('/chat/{conversation}/messages/{message}/cancel', [ChatController::class, 'cancelOffer'])
        ->scopeBindings()
        ->name('chat.offers.cancel');
    Route::delete('/chat/messages/{message}', [ChatController::class, 'destroy'])
        ->name('chat.messages.destroy');
});

// Lokasi hasil deteksi browser [ALDI]
Route::post('/location', [LocationController::class, 'store'])
    ->middleware(['auth', 'banned', 'throttle:30,1'])
    ->name('location.store');

// Notifikasi [ALDI]
Route::middleware(['auth', 'banned'])->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read', [NotificationController::class, 'markAllRead'])->name('notifications.readAll');
});

// Listing & pencarian produk (statis, sebelum wildcard /{id})
Route::get('/products', [ProductController::class, 'index'])->name('products.index');

// Detail Barang & Laporan (wildcard /{id} — ditaruh paling bawah rute produk)
Route::get('/products/{id}', [ProductDetailController::class, 'show'])->name('products.show');
Route::post('/products/{id}/report', [ProductDetailController::class, 'report'])->name('products.report');
