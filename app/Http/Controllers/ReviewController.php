<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    /**
     * Simpan ulasan pembeli untuk satu produk.
     *
     * Hanya pembeli dari order berstatus "selesai" yang boleh memberi ulasan,
     * dan hanya sekali per order (unik di kolom order_id). Setiap ulasan
     * melekat pada satu produk sehingga tidak tercampur dengan produk lain,
     * namun tetap ikut menyumbang rating agregat penjual di halaman profil.
     */
    public function store(StoreReviewRequest $request, Product $product): RedirectResponse
    {
        $buyerId = (string) Auth::id();

        abort_if($product->user_id === $buyerId, 403, 'Anda tidak bisa mengulas produk sendiri.');

        // Produk yang ditangguhkan/draft dan penjual yang dibanned tetap 404
        // terhadap tampilan publik, mengikuti aturan di detail produk.
        if (! Auth::user()?->isAdmin() && $product->user?->is_banned) {
            abort(404);
        }

        $order = Order::query()
            ->where('product_id', $product->id)
            ->where('buyer_id', $buyerId)
            ->where('status', Order::STATUS_SELESAI)
            ->latest('id')
            ->first();

        abort_if($order === null, 403, 'Anda belum dapat mengulas produk ini.');

        abort_if(Review::where('order_id', $order->id)->exists(), 409, 'Anda sudah pernah mengulas produk ini.');

        Review::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'reviewer_id' => $buyerId,
            'seller_id' => $product->user_id,
            'rating' => $request->integer('rating'),
            'komentar' => $request->filled('komentar') ? trim($request->input('komentar')) : null,
        ]);

        return back()->with('success', 'Ulasan Anda berhasil dikirim. Terima kasih!');
    }

    /**
     * Perbarui ulasan milik pembeli itu sendiri.
     */
    public function update(StoreReviewRequest $request, Product $product, Review $review): RedirectResponse
    {
        $this->authorizeOwner($product, $review);

        $review->update([
            'rating' => $request->integer('rating'),
            'komentar' => $request->filled('komentar') ? trim($request->input('komentar')) : null,
        ]);

        return back()->with('success', 'Ulasan Anda berhasil diperbarui.');
    }

    /**
     * Hapus ulasan milik pembeli itu sendiri.
     */
    public function destroy(Product $product, Review $review): RedirectResponse
    {
        $this->authorizeOwner($product, $review);

        $review->delete();

        return back()->with('success', 'Ulasan Anda telah dihapus.');
    }

    /**
     * Hanya pemilik ulasan yang boleh mengubah/menghapus, dan ulasan harus
     * milik produk yang sedang dilihat.
     */
    private function authorizeOwner(Product $product, Review $review): void
    {
        abort_if($review->product_id !== $product->id, 404);
        abort_if($review->reviewer_id !== (string) Auth::id(), 403, 'Anda tidak berhak mengelola ulasan ini.');
    }
}
