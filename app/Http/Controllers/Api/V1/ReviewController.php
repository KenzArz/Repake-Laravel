<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Order;
use App\Models\Review;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(StoreReviewRequest $request, Order $order): JsonResponse
    {
        $userId = (string) $request->user()->id;

        abort_unless($userId === $order->buyer_id || $userId === $order->seller_id, 403, 'Anda bukan pihak pada transaksi ini.');
        abort_unless($order->status === Order::STATUS_SELESAI, 403, 'Transaksi ini belum dapat diulas.');
        abort_if($order->reviews()->where('reviewer_id', $userId)->exists(), 409, 'Anda sudah pernah mengulas transaksi ini.');

        $isBuyer = $userId === $order->buyer_id;

        $review = Review::create([
            'order_id' => $order->id,
            'product_id' => $isBuyer ? $order->product_id : null,
            'reviewer_id' => $userId,
            'reviewee_id' => $isBuyer ? $order->seller_id : $order->buyer_id,
            'rating' => $request->integer('rating'),
            'komentar' => filled($request->input('komentar')) ? trim((string) $request->input('komentar')) : null,
        ]);

        $review->load('reviewer', 'product.primaryImage');

        return ApiResponse::created(new ReviewResource($review), 'Ulasan Anda berhasil dikirim.');
    }

    public function update(StoreReviewRequest $request, Review $review): JsonResponse
    {
        $this->authorizeOwner($review, $request);

        $review->update([
            'rating' => $request->integer('rating'),
            'komentar' => filled($request->input('komentar')) ? trim((string) $request->input('komentar')) : null,
        ]);

        $review->load('reviewer', 'product.primaryImage');

        return ApiResponse::success(new ReviewResource($review), 'Ulasan Anda berhasil diperbarui.');
    }

    public function destroy(Request $request, Review $review): JsonResponse
    {
        $this->authorizeOwner($review, $request);
        $review->delete();

        return ApiResponse::success(null, 'Ulasan Anda telah dihapus.');
    }

    private function authorizeOwner(Review $review, Request $request): void
    {
        abort_if($review->reviewer_id !== (string) $request->user()->id, 403, 'Anda tidak berhak mengelola ulasan ini.');
    }
}
