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

        // Produk yang disuspend/draft/terjual dan produk milik penjual yang
        // dibanned tidak boleh diakses publik lewat URL langsung. Pemilik,
        // admin, dan pembeli yang punya transaksi pada produk ini tetap bisa
        // membukanya (mis. untuk mengulas produk yang sudah terjual).
        $hasOrder = $viewer !== null && Order::query()
            ->where('product_id', $product->id)
            ->where('buyer_id', $viewer->id)
            ->exists();

        $hiddenStatuses = [Product::STATUS_DRAFT, Product::STATUS_DITANGGUIHKAN, Product::STATUS_DITINJAU, Product::STATUS_TERJUAL];

        abort_if(! $isAdmin && ! $isOwner && ! $hasOrder && in_array($product->status, $hiddenStatuses, true), 404);
        abort_if(! $isAdmin && ! $hasOrder && $product->user?->is_banned, 404);

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

        // Statistik ulasan penjual dari database (semua ulasan yang diterimanya)
        $stats = Review::where('reviewee_id', $product->user_id)
            ->selectRaw('COUNT(*) as total, COALESCE(AVG(rating), 0) as avg_rating')
            ->first();

        $sellerUser = $product->user;
        $seller = (object) [
            'name' => $sellerUser?->name ?? 'Penjual RePake',
            'kota' => $sellerUser?->kota ?? 'Karawang',
            'avatar_url' => $sellerUser?->avatar_url ?? asset('images/default-avatar.png'),
            'rating' => round((float) $stats->avg_rating, 1),
            'reviews_count' => (int) $stats->total,
            'online_status' => $sellerUser?->isOnline() ? 'Online' : 'Terakhir dilihat',
            'last_seen_at' => $sellerUser?->last_seen_at,
            'verified' => $sellerUser !== null && ! $sellerUser->is_banned,
            'joined' => $sellerUser?->created_at
                ? 'Bergabung '.$sellerUser->created_at->timezone('Asia/Jakarta')->locale('id')->translatedFormat('F Y')
                : 'Bergabung baru',
        ];

        // Ulasan khusus produk ini (review per produk, tidak tercampur produk lain).
        $productReviews = Review::with(['reviewer'])
            ->where('product_id', $product->id)
            ->latest()
            ->get();

        // Order pembeli ini pada produk ini. Ulasan hanya dibuka setelah
        // transaksi selesai, jadi order aktif (menunggu COD) tidak dihitung.
        $viewerOrder = $viewer === null
            ? null
            : Order::query()
                ->where('product_id', $product->id)
                ->where('buyer_id', $viewer->id)
                ->where('status', Order::STATUS_SELESAI)
                ->latest('id')
                ->first();

        // Ulasan milik pengunjung untuk order selesai terbaru. Ulasan dibuka
        // per transaksi, jadi pembelian ulang pada produk yang sama tetap
        // mendapat kesempatan mengulas order barunya.
        $viewerReview = $viewer !== null && $viewerOrder !== null
            ? $viewerOrder->reviewBy($viewer->id)
            : null;

        // Order aktif (menunggu COD) milik pengunjung untuk banner status di
        // detail produk, baik sebagai pembeli maupun penjual.
        $activeOrder = $viewer === null
            ? null
            : Order::query()
                ->where('product_id', $product->id)
                ->where(fn ($q) => $q
                    ->where('buyer_id', $viewer->id)
                    ->orWhere('seller_id', $viewer->id))
                ->where('status', Order::STATUS_DISEPAKATI)
                ->latest('id')
                ->first();

        $canReview = $viewer !== null
            && ! $isOwner
            && $viewerReview === null
            && $viewerOrder !== null;

        return view('products.show', [
            'product' => $product,
            'seller' => $seller,
            'isOwner' => $isOwner,
            'similarProducts' => $similarProducts,
            'productReviews' => $productReviews,
            'viewerOrder' => $viewerOrder,
            'activeOrder' => $activeOrder,
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
