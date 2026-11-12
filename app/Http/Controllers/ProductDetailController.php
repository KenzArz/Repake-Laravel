<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReportRequest;
use App\Models\Product;
use App\Models\Report;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductDetailController extends Controller
{
    /**
     * Tampilkan halaman detail barang.
     */
    public function show(Request $request, string $slug): View
    {
        $baseQuery = Product::with(['images', 'primaryImage', 'user', 'codAreas', 'category'])
            ->withCount('favorites');

        $product = (clone $baseQuery)->where('slug', $slug)->first()
            ?? (ctype_digit($slug) ? $baseQuery->find((int) $slug) : null);

        abort_if(! $product, 404);

        $viewer = $request->user();
        $isOwner = $viewer !== null && (int) $viewer->id === (int) $product->user_id;
        $isAdmin = $viewer?->isAdmin() ?? false;

        // Produk yang disuspend/draft dan produk milik penjual yang dibanned
        // tidak boleh diakses publik lewat URL langsung.
        $hiddenStatuses = [Product::STATUS_DRAFT, Product::STATUS_DITANGGUIHKAN];

        abort_if(! $isAdmin && ! $isOwner && in_array($product->status, $hiddenStatuses, true), 404);
        abort_if(! $isAdmin && $product->user?->is_banned, 404);

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

        return view('products.show', [
            'product' => $product,
            'seller' => $seller,
            'similarProducts' => $similarProducts,
        ]);
    }

    /**
     * Simpan laporan pengaduan barang ke database.
     */
    public function report(StoreReportRequest $request, int|string $id): RedirectResponse
    {
        $validated = $request->validated();

        $product = Product::where('slug', (string) $id)->first()
            ?? (ctype_digit((string) $id) ? Product::find((int) $id) : null);

        abort_if(! $product, 404);

        // Pemilik tidak bisa melaporkan produknya sendiri.
        abort_if($product->user_id === (int) auth()->id(), 403);

        $report = Report::create([
            'product_id' => $product->id,
            'reporter_id' => auth()->id(),
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
