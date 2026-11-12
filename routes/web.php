<?php

use App\Http\Controllers\Admin\BannerController as AdminBannerController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
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

    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

    // Laporan produk (wajib login)
    Route::post('/products/{id}/report', [ProductDetailController::class, 'report'])
        ->middleware(['auth', 'banned'])
        ->name('products.report');
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

// Profil publik penjual lain (dibuka dari detail produk, kartu produk, dan header chat).
// Sengaja di luar middleware auth agar tamu yang melihat detail produk tetap bisa membukanya.
Route::get('/penjual/{seller}', [ProfileController::class, 'show'])->name('sellers.show');

// Admin (wajib login + allowlist email admin)
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'banned', 'admin'])
    ->group(function () {
        Route::redirect('/', '/admin/dashboard');

        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        // Detail & tindak lanjut laporan.
        Route::get('/reports/{report}', [AdminReportController::class, 'show'])->name('reports.show');
        Route::patch('/reports/{report}/moderate', [AdminReportController::class, 'moderate'])->name('reports.moderate');

        // Kategori (index dialihkan ke tab kategori di dashboard).
        Route::resource('categories', AdminCategoryController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        // Banner beranda.
        Route::post('/banners', [AdminBannerController::class, 'store'])->name('banners.store');
        Route::patch('/banners/reorder', [AdminBannerController::class, 'reorder'])->name('banners.reorder');
        Route::patch('/banners/{banner}/toggle', [AdminBannerController::class, 'toggle'])->name('banners.toggle');
        Route::patch('/banners/{banner}/move', [AdminBannerController::class, 'move'])->name('banners.move');
        Route::delete('/banners/{banner}', [AdminBannerController::class, 'destroy'])->name('banners.destroy');
    });

// Chat (wajib login)
Route::middleware(['auth', 'banned'])->group(function () {
    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::post('/chat', [ChatController::class, 'start'])->name('chat.store');

    // Rute statis harus sebelum wildcard /chat/{conversation}.
    Route::get('/chat/unread-count', [ChatController::class, 'unreadCount'])
        ->name('chat.unread');
    Route::get('/chat/{conversation}', [ChatController::class, 'show'])
        ->scopeBindings()
        ->name('chat.show');
    Route::post('/chat/{conversation}/read', [ChatController::class, 'markRead'])
        ->name('chat.read');
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

// Listing & pencarian produk (statis, sebelum wildcard /{slug})
Route::get('/products', [ProductController::class, 'index'])->name('products.index');

// Detail Barang & Laporan.
// Slug di path hanya kosmetik; identitas produk ada di query `?id={uuid}`.
Route::get('/produk/{slug}', [ProductDetailController::class, 'show'])->name('products.show');

// URL lama tetap hidup: dialihkan permanen ke URL kanonis berbasis UUID.
Route::get('/products/{slug}', [ProductDetailController::class, 'legacy'])->name('products.legacy');

Route::get('/api/search/suggestions', [ProductController::class, 'suggestions'])
    ->name('api.search.suggestions');
