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
            ->when($q !== '', fn ($query) => $query->cari($q))
            ->when($kota !== '', fn ($query) => $query->where('kota', $kota))
            ->when($kategori !== '', fn ($query) => $query->whereHas('category', fn ($cq) => $cq->where('slug', $kategori)))
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
                'Maksimal gambar yang dapat diunggah adalah '.ProductImageStorage::MAX_IMAGES.' gambar.',
                ['images' => ['Maksimal gambar yang dapat diunggah adalah '.ProductImageStorage::MAX_IMAGES.' gambar.']],
                $request,
            );
        }

        try {
            $this->publisher->publish(Auth::id(), [
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
        } catch (\Throwable) {
            return $this->error(
                'Gagal mengunggah produk. Silakan coba lagi atau gunakan gambar lain.',
                ['images' => ['Gagal menyimpan gambar produk. Coba lagi.']],
                $request,
            );
        }

        if ($request->wantsJson()) {
            session()->flash('success', 'Produk berhasil dipasang!');

            return response()->json(['redirect' => route('profile.catalog')]);
        }

        return redirect()->route('profile.catalog')->with('success', 'Produk berhasil dipasang!');
    }

    private function error(string $message, array $errors, StoreProductRequest $request): RedirectResponse|JsonResponse
    {
        if ($request->wantsJson()) {
            return response()->json(['message' => $message, 'errors' => $errors], 422);
        }

        return back()->withErrors($errors)->withInput();
    }
}
