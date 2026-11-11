<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function create()
    {
        $categories = Category::orderBy('name')->get();

        return view('products.create', compact('categories'));
    }

    public function store(StoreProductRequest $request)
    {
        DB::transaction(function () use ($request) {
            $description = $request->description;
            if ($request->filled('location_notes')) {
                $description .= "\n\nPatokan Lokasi: " . $request->location_notes;
            }

            $product = Product::create([
                'user_id'           => Auth::id(),
                'category_id'       => $request->category_id,
                'title'             => $request->title,
                'description'       => $description,
                'price'             => $request->price,
                'condition'         => $request->condition,
                'status'            => 'tersedia',
                'moderation_status' => 'active',
            ]);

            if ($request->hasFile('images')) {
                // Batasi maksimal 3 file pada level server untuk keamanan tambahan
                $uploadedFiles = array_slice($request->file('images'), 0, 3);

                foreach ($uploadedFiles as $index => $file) {
                    $filename = 'products/' . Str::random(40) . '.webp';

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
                        'path'       => Storage::url($filename),
                        'is_primary' => $index === 0, // Indeks pertama sebagai Cover Utama
                        'sort_order' => $index,
                    ]);
                }
            }
        });

        return redirect()->route('profile.catalog')->with('success', 'Produk berhasil dipasang!');
    }
}
