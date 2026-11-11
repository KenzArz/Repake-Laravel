<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function create(): View
    {
        $categories = Category::orderBy('name')->get();

        return view('products.create', compact('categories'));
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $product = Product::create([
                'user_id' => Auth::id(),
                'category_id' => $request->category_id,
                'judul' => $request->judul,
                'slug' => $this->uniqueSlug($request->judul),
                'harga' => (int) $request->harga,
                'kondisi' => $request->kondisi,
                'status' => Product::STATUS_DIJUAL,
                'deskripsi' => $request->deskripsi,
                'kota' => $request->kota,
                'patokan_lokasi' => $request->patokan_lokasi,
            ]);

            // Batasi maksimal 3 file pada level server untuk keamanan tambahan
            $uploadedFiles = array_slice($request->file('images'), 0, 3);

            foreach ($uploadedFiles as $index => $file) {
                $filename = 'products/'.Str::random(40).'.webp';

                // 1. Baca data gambar
                $imageContent = file_get_contents($file->getRealPath());
                $gdImage = @imagecreatefromstring($imageContent);

                if ($gdImage !== false) {
                    // 2. Pertahankan transparansi (jika PNG/WebP asal)
                    imagealphablending($gdImage, true);
                    imagesavealpha($gdImage, true);

                    // 3. Konversi ke WebP dengan kompresi kualitas 80%
                    ob_start();
                    imagewebp($gdImage, null, 80);
                    $webpData = ob_get_clean();
                    imagedestroy($gdImage);

                    // 4. Simpan file .webp ke Storage
                    Storage::disk('public')->put($filename, $webpData);
                } else {
                    // Fallback jika format file tidak bisa diolah GD
                    $filename = $file->store('products', 'public');
                }

                ProductImage::create([
                    'product_id' => $product->id,
                    'path' => $filename,
                    'is_cover' => $index === 0, // Indeks pertama sebagai Cover Utama
                    'urutan' => $index,
                ]);
            }
        });

        return redirect()->route('profile.catalog')->with('success', 'Produk berhasil dipasang!');
    }

    /**
     * Slug unik dari judul; diberi suffix angka bila sudah dipakai produk lain.
     */
    private function uniqueSlug(string $judul): string
    {
        $slug = Str::slug($judul);
        $candidate = $slug;
        $suffix = 2;

        while (Product::withTrashed()->where('slug', $candidate)->exists()) {
            $candidate = $slug.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }
}
