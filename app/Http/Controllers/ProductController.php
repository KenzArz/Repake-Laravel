<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductImageStorage;
use App\Services\ProductPublisher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductImageStorage $images,
        private readonly ProductPublisher $publisher,
    ) {}

    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q'));
        $kategori = (string) $request->query('kategori');
        $kota = trim((string) $request->query('kota'));

        $products = Product::with(['primaryImage', 'user', 'category'])
            ->when($q !== '', fn($query) => $query->cari($q))
            ->when($kota !== '', fn($query) => $query->where('kota', $kota))
            ->when($kategori !== '', fn($query) => $query->whereHas('category', fn($cq) => $cq->where('slug', $kategori)))
            ->dijual()
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $categories = Category::urut()->get();

        return view('products.index', compact('products', 'categories', 'q', 'kategori', 'kota'));
    }

    public function create(): View
    {
        $categories = Category::urut()->get();

        return view('products.create', compact('categories'));
    }

    public function store(StoreProductRequest $request): RedirectResponse|JsonResponse
    {
        $files = $this->images->validFiles($request->file('images'));

        if ($files->isEmpty()) {
            return $this->error(
                'Wajib mengunggah minimal 1 gambar produk.',
                ['images' => ['Wajib mengunggah minimal 1 gambar produk.']],
                $request,
            );
        }

        if ($files->count() > ProductImageStorage::MAX_IMAGES) {
            return $this->error(
                'Maksimal gambar yang dapat diunggah adalah ' . ProductImageStorage::MAX_IMAGES . ' gambar.',
                ['images' => ['Maksimal gambar yang dapat diunggah adalah ' . ProductImageStorage::MAX_IMAGES . ' gambar.']],
                $request,
            );
        }

        try {
            $product = $this->publisher->publish(Auth::id(), [
                'category_id' => $request->category_id,
                'judul' => $request->judul,
                'slug' => Product::uniqueSlug($request->judul),
                'harga' => (int) $request->harga,
                'kondisi' => $request->kondisi,
                'status' => Product::STATUS_DIJUAL,
                'deskripsi' => $request->deskripsi,
                'kota' => $request->kota,
                'patokan_lokasi' => $request->patokan_lokasi,
            ], $files);

            if ($product instanceof Product && $request->filled(['cod_lat', 'cod_lng'])) {
                $placeName = $request->cod_place_name ?: $request->kota;

                $codArea = \App\Models\CodArea::firstOrCreate(
                    ['name' => $placeName, 'city' => $request->kota],
                    [
                        'province' => $request->province ?? 'Jawa Barat',
                        'boundary' => [
                            'type' => 'Point',
                            'coordinates' => [(float) $request->cod_lng, (float) $request->cod_lat],
                        ],
                    ]
                );

                $product->codAreas()->sync([$codArea->id]);
            }
        } catch (\Throwable) {
            return $this->error(
                'Gagal mengunggah produk. Silakan coba lagi atau gunakan gambar lain.',
                ['images' => ['Gagal menyimpan gambar produk. Coba lagi.']],
                $request,
            );
        }

        $judul = $product instanceof Product ? $product->judul : $request->judul;
        $toastMessage = "Produk \"{$judul}\" berhasil dipasang!";

        if ($request->wantsJson()) {
            session()->flash('success', $toastMessage);

            return response()->json(['redirect' => route('profile.catalog')]);
        }

        return redirect()
            ->route('profile.catalog')
            ->with('success', $toastMessage);
    }

    private function error(string $message, array $errors, StoreProductRequest $request): RedirectResponse|JsonResponse
    {
        if ($request->wantsJson()) {
            return response()->json(['message' => $message, 'errors' => $errors], 422);
        }

        return back()->withErrors($errors)->withInput();
    }

    /**
     * Tampilkan form edit produk milik penjual.
     */
    public function edit(Product $product): View
    {
        abort_if((int) $product->user_id !== (int) Auth::id(), 403);

        $product->load(['images', 'codAreas', 'category']);

        return view('products.edit', [
            'product' => $product,
            'categories' => Category::urut()->get(),
        ]);
    }

    /**
     * Simpan perubahan data produk.
     */
    public function update(Request $request, Product $product): RedirectResponse|JsonResponse
    {
        abort_if((int) $product->user_id !== (int) Auth::id(), 403);

        $validated = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'harga' => ['required', 'integer', 'min:0'],
            'kondisi' => ['required', 'string', 'max:50'],
            'status' => ['sometimes', Rule::in([
                Product::STATUS_DRAFT,
                Product::STATUS_DIJUAL,
                Product::STATUS_TERJUAL,
            ])],
            'deskripsi' => ['required', 'string'],
            'kota' => ['required', 'string', 'max:100'],
            'cod_place_name' => ['nullable', 'string', 'max:150'],
            'province' => ['nullable', 'string', 'max:100'],
            'cod_lat' => ['nullable', 'numeric'],
            'cod_lng' => ['nullable', 'numeric'],
            'patokan_lokasi' => ['nullable', 'string', 'max:255'],
            'is_negotiable' => ['sometimes', 'boolean'],
            'cover_image_id' => ['nullable', 'integer'],
            'delete_images' => ['nullable', 'array'],
            'delete_images.*' => ['integer'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ]);

        $newFiles = $this->images->validFiles($request->file('images'));
        $deleteIds = collect($validated['delete_images'] ?? [])->map(fn($id) => (int) $id)->all();

        $product->load('images');
        $remainingExisting = $product->images->whereNotIn('id', $deleteIds);
        $totalPhotos = $remainingExisting->count() + $newFiles->count();

        if ($totalPhotos < 1) {
            return back()
                ->withErrors(['images' => 'Produk wajib memiliki minimal 1 gambar.'])
                ->withInput();
        }

        if ($totalPhotos > ProductImageStorage::MAX_IMAGES) {
            return back()
                ->withErrors(['images' => 'Maksimal total gambar produk adalah ' . ProductImageStorage::MAX_IMAGES . ' gambar.'])
                ->withInput();
        }

        $product->update([
            'judul' => $validated['judul'],
            'category_id' => $validated['category_id'],
            'harga' => (int) $validated['harga'],
            'kondisi' => $validated['kondisi'],
            'status' => $validated['status'] ?? $product->status,
            'deskripsi' => $validated['deskripsi'],
            'kota' => $validated['kota'],
            'patokan_lokasi' => $validated['patokan_lokasi'] ?? null,
            'is_negotiable' => $request->boolean('is_negotiable', $product->is_negotiable),
        ]);

        // Update titik lokasi COD jika tersedia
        if ($request->filled(['cod_lat', 'cod_lng'])) {
            $placeName = $validated['cod_place_name'] ?: $validated['kota'];

            $codArea = \App\Models\CodArea::firstOrCreate(
                ['name' => $placeName, 'city' => $validated['kota']],
                [
                    'province' => $validated['province'] ?? 'Jawa Barat',
                    'boundary' => [
                        'type' => 'Point',
                        'coordinates' => [(float) $validated['cod_lng'], (float) $validated['cod_lat']],
                    ],
                ]
            );

            $product->codAreas()->sync([$codArea->id]);
        }

        // Hapus foto lama yang ditandai
        if ($deleteIds !== []) {
            $toDelete = $product->images()->whereIn('id', $deleteIds)->get();
            foreach ($toDelete as $img) {
                $rawPath = $img->getAttributes()['path'] ?? $img->getAttributes()['path_url'] ?? null;
                if ($rawPath) {
                    $diskPath = preg_replace('#^/?storage/#', '', $rawPath);
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($diskPath);
                }
                $img->delete();
            }
        }

        // Simpan foto baru yang diunggah
        // Simpan foto baru yang diunggah
        $sampleAttrs = $product->images()->first()?->getAttributes() ?? [];
        $pathColumn = array_key_exists('path', $sampleAttrs) ? 'path' : 'path_url';
        $nextUrutan = ((int) $product->images()->max('urutan')) + 1;

        $savedNewImages = [];
        foreach ($newFiles as $file) {
            $storedPath = $file->store('products', 'public');
            $valueToSave = $pathColumn === 'path_url' ? 'storage/' . $storedPath : $storedPath;

            $savedNewImages[] = $product->images()->create([
                $pathColumn => $valueToSave,
                'urutan' => $nextUrutan++,
                'is_cover' => false,
            ]);
        }

        // Terapkan urutan gambar & set urutan ke-0 sebagai Cover Utama
        $existingMap = $product->images()->get()->keyBy('id');
        $imageOrder = $request->input('image_order', []);

        if (is_array($imageOrder) && $imageOrder !== []) {
            $product->images()->update(['is_cover' => false]);
            $urutan = 0;

            foreach ($imageOrder as $token) {
                if (! is_string($token) || ! str_contains($token, ':')) {
                    continue;
                }

                [$type, $rawIdx] = explode(':', $token, 2);
                $idx = (int) $rawIdx;

                $target = match ($type) {
                    'existing' => $existingMap->get($idx),
                    'new' => $savedNewImages[$idx] ?? null,
                    default => null,
                };

                if ($target) {
                    $product->images()->whereKey($target->id)->update([
                        'urutan' => $urutan,
                        'is_cover' => $urutan === 0,
                    ]);
                    $urutan++;
                }
            }
        }

        // Pengaman: pastikan selalu ada minimal 1 foto dengan is_cover = true
        if (! $product->images()->where('is_cover', true)->exists()) {
            $firstImage = $product->images()->orderBy('urutan')->first();
            if ($firstImage) {
                $product->images()->whereKey($firstImage->id)->update([
                    'is_cover' => true,
                    'urutan' => 0,
                ]);
            }
        }

        $toastMessage = "Perubahan pada produk \"{$product->judul}\" berhasil disimpan.";

        if ($request->wantsJson()) {
            session()->flash('success', $toastMessage);

            return response()->json(['redirect' => route('products.show', $product)]);
        }

        return redirect()
            ->route('products.show', $product)
            ->with('success', $toastMessage);
    }

    /**
     * Hapus produk (soft delete).
     */
    public function destroy(Product $product): RedirectResponse
    {
        abort_if((int) $product->user_id !== (int) Auth::id(), 403);

        $judul = $product->judul;
        $detailUrl = route('products.show', $product);
        $product->delete();

        $message = "Produk \"{$judul}\" berhasil dihapus dari katalog.";

        // Jika dihapus dari halaman detail produk, arahkan ke katalog profil agar tidak 404
        if (url()->previous() === $detailUrl) {
            return redirect()
                ->route('profile.catalog')
                ->with('success', $message);
        }

        return back()->with('success', $message);
    }
}
