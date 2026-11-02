<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReportRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProductDetailController extends Controller
{
    /**
     * Tampilkan halaman detail barang.
     */
    public function show(int|string $id): View
    {
        $product = Product::findDummy($id);

        if (! $product) {
            abort(404, 'Produk tidak ditemukan');
        }

        // Produk serupa / rekomendasi lainnya
        $similarProducts = Product::dummyCatalog()
            ->filter(fn (object $p): bool => (int) $p->id !== (int) $id)
            ->take(4)
            ->values();

        // Data penjual
        $seller = (object) [
            'name' => $product->penjual,
            'kota' => $product->kota,
            'rating' => 4.8,
            'reviews_count' => 32,
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
     * Simpan laporan pengaduan barang.
     */
    public function report(StoreReportRequest $request, int|string $id): RedirectResponse
    {
        $validated = $request->validated();
        $product = Product::findDummy($id);

        if (! $product) {
            abort(404, 'Produk tidak ditemukan');
        }

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
