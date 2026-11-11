<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReportRequest;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProductDetailController extends Controller
{
    /**
     * Tampilkan halaman detail barang.
     */
    public function show(int|string $id): View
    {
        $product = Product::with(['images', 'primaryImage', 'user', 'codAreas', 'category'])
            ->findOrFail($id);

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
     * Simpan laporan pengaduan barang (disimpan di session untuk saat ini).
     */
    public function report(StoreReportRequest $request, int|string $id): RedirectResponse
    {
        $validated = $request->validated();

        // Pastikan produk benar-benar ada di database
        Product::findOrFail($id);

        $reports = session()->get('reports', []);
        $reports[] = [
            'id' => count($reports) + 1,
            'product_id' => (int) $id,
            'alasan' => $validated['alasan'],
            'keterangan' => $validated['keterangan'],
            'created_at' => now()->toDateTimeString(),
        ];
        session()->put('reports', $reports);

        return back()->with('report_success', 'Laporan Anda berhasil dikirim. Tim RePake akan segera meninjau barang ini.');
    }
}
