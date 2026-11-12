<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReportRequest;
use App\Models\Order;
use App\Models\Product;
use App\Models\Report;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductDetailController extends Controller
{
    /**
     * Tampilkan halaman detail barang.
     *
     * Identitas produk diambil dari query `?id={uuid}`. Segmen `{slug}`
     * hanya untuk keterbacaan URL, sehingga produk berjudul sama tetap
     * dibedakan oleh UUID dan rename judul tidak mematikan link lama.
     */
    public function show(Request $request, string $slug): View|RedirectResponse
    {
        $baseQuery = Product::with(['images', 'primaryImage', 'user', 'codAreas', 'category'])
            ->withCount('favorites');

        $id = $request->query('id');

        if (filled($id)) {
            abort_unless(is_string($id) && Str::isUuid($id), 404);

            $product = (clone $baseQuery)->find($id);
        } else {
            $product = (clone $baseQuery)->where('slug', $slug)->first();
        }

        abort_if($product === null, 404);

        $viewer = $request->user();
        $isOwner = $viewer !== null && $viewer->id === $product->user_id;
        $isAdmin = $viewer?->isAdmin() ?? false;

        // Produk yang disuspend/draft dan produk milik penjual yang dibanned
        // tidak boleh diakses publik lewat URL langsung.
        $hiddenStatuses = [Product::STATUS_DRAFT, Product::STATUS_DITANGGUIHKAN];

        abort_if(! $isAdmin && ! $isOwner && in_array($product->status, $hiddenStatuses, true), 404);
        abort_if(! $isAdmin && $product->user?->is_banned, 404);

        // Satu produk = satu URL kanonis. Link tanpa UUID atau dengan slug
        // usang dialihkan permanen agar tidak ada konten duplikat.
        if (! $request->filled('id') || $product->slug !== $slug) {
            return redirect()->to($product->detailUrl(), 301);
        }

        // Produk serupa / rekomendasi lainnya dari kategori yang sama
        $similarProducts = Product::with(['primaryImage', 'user'])
            ->when($product->category_id, fn ($q) => $q->where('category_id', $product->category_id))
            ->whereKeyNot($product->getKey())
            ->dijual()
            ->latest()
            ->take(4)
            ->get();

        // Statistik ulasan penjual dari database
        $stats = Review::where('seller_id', $product->user_id)
            ->selectRaw('COUNT(*) as total, COALESCE(AVG(rating), 0) as avg_rating')
            ->first();

        $seller = (object) [
            'name' => $product->user?->name ?? 'Penjual RePake',
            'kota' => $product->user?->kota ?? 'Karawang',
            'rating' => round((float) $stats->avg_rating, 1),
            'reviews_count' => (int) $stats->total,
            'online_status' => 'Aktif 15 menit lalu',
            'verified' => true,
            'joined' => 'Bergabung 6 bulan lalu',
        ];

        // Ulasan khusus produk ini (review per produk, tidak tercampur produk lain).
        $productReviews = Review::with(['reviewer'])
            ->where('product_id', $product->id)
            ->latest()
            ->get();

        // Kondisi form ulasan untuk pengunjung yang login.
        $viewerReview = $viewer !== null
            ? $productReviews->first(fn (Review $review): bool => $review->reviewer_id === $viewer->id)
            : null;

        $canReview = $viewer !== null
            && ! $isOwner
            && $viewerReview === null
            && Order::query()
                ->where('product_id', $product->id)
                ->where('buyer_id', $viewer->id)
                ->where('status', Order::STATUS_SELESAI)
                ->exists();

        return view('products.show', [
            'product' => $product,
            'seller' => $seller,
            'similarProducts' => $similarProducts,
            'productReviews' => $productReviews,
            'viewerReview' => $viewerReview,
            'canReview' => $canReview,
        ]);
    }

    /**
     * Alihkan URL produk versi lama (/products/{slug}) ke URL kanonis.
     */
    public function legacy(string $slug): RedirectResponse
    {
        $product = Product::query()->where('slug', $slug)->first();

        abort_if($product === null, 404);

        return redirect()->to($product->detailUrl(), 301);
    }

    /**
     * Simpan laporan pengaduan barang ke database.
     */
    public function report(StoreReportRequest $request, string $id): RedirectResponse
    {
        $validated = $request->validated();

        abort_unless(Str::isUuid($id), 404);

        $product = Product::query()->find($id);

        abort_if($product === null, 404);

        // Pemilik tidak bisa melaporkan produknya sendiri.
        abort_if($product->user_id === $request->user()?->id, 403);

        Report::create([
            'product_id' => $product->id,
            'reporter_id' => $request->user()?->id,
            'alasan' => $validated['alasan'],
            'keterangan' => $validated['keterangan'],
            'bukti' => $request->hasFile('bukti')
                ? $request->file('bukti')->store('reports', 'public')
                : null,
            'status' => Report::STATUS_PENDING,
        ]);

        return back()->with('report_success', 'Laporan Anda berhasil dikirim. Tim RePake akan segera meninjau barang ini.');
    }
}
