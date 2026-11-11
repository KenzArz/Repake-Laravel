<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReportRequest;
use App\Models\Product;
use App\Models\Report;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProductDetailController extends Controller
{
    /**
     * Tampilkan halaman detail barang.
     */
    public function show(int|string $id): View
    {
        $product = Product::with(['user', 'primaryImage', 'images', 'codAreas', 'category'])
            ->where('moderation_status', 'active')
            ->findOrFail($id);

        // Rekomendasi produk serupa berdasarkan kategori yang sama
        $similarProducts = Product::with(['user', 'primaryImage', 'images', 'codAreas', 'category'])
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('moderation_status', 'active')
            ->take(4)
            ->get();

        $seller = (object) [
            'name' => $product->user->name ?? 'Mahasiswa UNSIKA',
            'kota' => $product->user->city ?? 'Karawang',
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
        $product = Product::findOrFail($id);

        if (Auth::check()) {
            Report::create([
                'reporter_id' => Auth::id(),
                'reported_user_id' => $product->user_id,
                'product_id' => $product->id,
                'reason' => $validated['alasan'],
                'description' => $validated['keterangan'],
                'status' => 'pending',
            ]);
        }

        return back()->with('report_success', 'Laporan Anda berhasil dikirim. Tim RePake akan segera meninjau barang ini.');
    }
}
