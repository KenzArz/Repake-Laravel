<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Models\Order;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    /**
     * Simpan ulasan untuk satu transaksi (dua arah).
     *
     * - Pembeli -> Penjual: ulasan produk (product_id terisi), ikut rating
     *   agregat penjual.
     * - Penjual -> Pembeli: ulasan orang (product_id null), masuk ke rating
     *   profil pembeli karena pembeli tidak punya produk untuk diulas.
     *
     * Ulasan dibuka sejak tawaran disepakati dan maksimal sekali per pihak
     * per transaksi (unik di order_id + reviewer_id).
     */
    public function store(StoreReviewRequest $request, Order $order): RedirectResponse
    {
        $userId = (string) Auth::id();

        abort_unless(
            $userId === $order->buyer_id || $userId === $order->seller_id,
            403,
            'Anda bukan pihak pada transaksi ini.',
        );

        abort_unless(
            in_array($order->status, [Order::STATUS_DISEPAKATI, Order::STATUS_SELESAI], true),
            403,
            'Transaksi ini belum dapat diulas.',
        );

        abort_if(
            $order->reviews()->where('reviewer_id', $userId)->exists(),
            409,
            'Anda sudah pernah mengulas transaksi ini.',
        );

        $isBuyer = $userId === $order->buyer_id;

        Review::create([
            'order_id' => $order->id,
            'product_id' => $isBuyer ? $order->product_id : null,
            'reviewer_id' => $userId,
            'reviewee_id' => $isBuyer ? $order->seller_id : $order->buyer_id,
            'rating' => $request->integer('rating'),
            'komentar' => filled($request->input('komentar')) ? trim((string) $request->input('komentar')) : null,
        ]);

        return back()->with('success', 'Ulasan Anda berhasil dikirim. Terima kasih!');
    }

    /**
     * Perbarui ulasan milik pengguna itu sendiri.
     */
    public function update(StoreReviewRequest $request, Review $review): RedirectResponse
    {
        $this->authorizeOwner($review);

        $review->update([
            'rating' => $request->integer('rating'),
            'komentar' => filled($request->input('komentar')) ? trim((string) $request->input('komentar')) : null,
        ]);

        return back()->with('success', 'Ulasan Anda berhasil diperbarui.');
    }

    /**
     * Hapus ulasan milik pengguna itu sendiri.
     */
    public function destroy(Review $review): RedirectResponse
    {
        $this->authorizeOwner($review);

        $review->delete();

        return back()->with('success', 'Ulasan Anda telah dihapus.');
    }

    /**
     * Hanya penulis ulasan yang boleh mengubah/menghapus.
     */
    private function authorizeOwner(Review $review): void
    {
        abort_if($review->reviewer_id !== (string) Auth::id(), 403, 'Anda tidak berhak mengelola ulasan ini.');
    }
}
