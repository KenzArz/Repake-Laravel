<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ChatController;
use App\Http\Controllers\Api\V1\FavoriteController;
use App\Http\Controllers\Api\V1\HomeController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\ReviewController;
use App\Http\Middleware\TouchLastSeen;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (Repake /api/v1)
|--------------------------------------------------------------------------
|
| Konsumsi utama: aplikasi Flutter. Autentikasi memakai Sanctum token yang
| ditukar dari Firebase ID token (POST /auth/firebase).
|
*/

// --- Publik (tanpa login) ---
Route::prefix('v1')->group(function (): void {
    Route::post('/auth/firebase', [AuthController::class, 'login'])
        ->middleware('throttle:10,1');

    Route::get('/home', [HomeController::class, 'index']);
    Route::get('/categories', [CategoryController::class, 'index']);

    Route::get('/products', [ProductController::class, 'index']);
    // Rute statis harus dideklarasikan sebelum wildcard /products/{id}.
    Route::get('/products/suggestions', [ProductController::class, 'suggestions']);
    Route::get('/products/{id}/reviews', [ProductController::class, 'reviews'])->whereUuid('id');
    Route::get('/products/{id}', [ProductController::class, 'show'])->whereUuid('id');

    Route::get('/sellers/{seller}', [ProfileController::class, 'seller']);
});

// --- Terautentikasi (Sanctum token + akun tidak dibanned) ---
Route::middleware(['auth:sanctum', 'banned', TouchLastSeen::class])
    ->prefix('v1')
    ->group(function (): void {
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        // Profil & akun
        Route::get('/me', [ProfileController::class, 'index']);
        Route::patch('/me', [ProfileController::class, 'update']);
        Route::put('/me/location', [ProfileController::class, 'location']);
        Route::get('/me/products', [ProfileController::class, 'products']);
        Route::get('/me/reviews', [ProfileController::class, 'reviews']);

        // Favorit
        Route::get('/favorites', [FavoriteController::class, 'index']);
        Route::post('/favorites/{id}/toggle', [FavoriteController::class, 'toggle'])->whereUuid('id');

        // Produk (tulis)
        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{id}', [ProductController::class, 'update'])->whereUuid('id');
        Route::delete('/products/{id}', [ProductController::class, 'destroy'])->whereUuid('id');
        Route::post('/products/{id}/report', [ProductController::class, 'report'])->whereUuid('id');

        // Order & ulasan
        Route::get('/orders', [OrderController::class, 'index']);
        Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus']);
        Route::post('/orders/{order}/reviews', [ReviewController::class, 'store']);
        Route::patch('/reviews/{review}', [ReviewController::class, 'update']);
        Route::delete('/reviews/{review}', [ReviewController::class, 'destroy']);

        // Chat & tawaran
        Route::get('/chats', [ChatController::class, 'index']);
        Route::post('/chats', [ChatController::class, 'start']);
        Route::get('/chats/unread-count', [ChatController::class, 'unreadCount']);
        // Hapus pesan sebelum wildcard /chats/{conversation}.
        Route::delete('/chats/messages/{message}', [ChatController::class, 'destroy']);
        Route::get('/chats/{conversation}', [ChatController::class, 'show']);
        Route::get('/chats/{conversation}/messages', [ChatController::class, 'poll']);
        Route::post('/chats/{conversation}/messages', [ChatController::class, 'send']);
        Route::post('/chats/{conversation}/read', [ChatController::class, 'read']);
        Route::put('/chats/{conversation}/messages/{message}/offer', [ChatController::class, 'updateOffer']);
        Route::post('/chats/{conversation}/messages/{message}/cancel', [ChatController::class, 'cancelOffer']);
        Route::post('/chats/{conversation}/messages/{message}/accept', [ChatController::class, 'acceptOffer']);
        Route::post('/chats/{conversation}/messages/{message}/reject', [ChatController::class, 'rejectOffer']);
        Route::post('/chats/{conversation}/sold', [ChatController::class, 'sold']);

        // Notifikasi
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    });
