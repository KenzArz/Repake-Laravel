<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;

class ProductController extends Controller
{
    /**
     * Listing & pencarian produk (digunakan halaman /products).
     */
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
        $files = collect($request->file('images') ?? [])
            ->filter(fn ($file): bool => $file instanceof UploadedFile && $file->isValid())
            ->values()
            ->take(3);

        if ($files->isEmpty()) {
            return $this->error(
                'Wajib mengunggah minimal 1 gambar produk.',
                ['images' => ['Wajib mengunggah minimal 1 gambar produk.']],
                $request,
            );
        }

        if ($files->count() > 3) {
            return $this->error(
                'Maksimal gambar yang dapat diunggah adalah 3 gambar.',
                ['images' => ['Maksimal gambar yang dapat diunggah adalah 3 gambar.']],
                $request,
            );
        }

        $written = [];

        try {
            DB::transaction(function () use ($request, $files, &$written) {
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

                $urutan = 0;

                foreach ($files as $file) {
                    $filename = $this->storeImage($file);

                    if ($filename === null) {
                        continue;
                    }

                    ProductImage::create([
                        'product_id' => $product->id,
                        'path' => $filename,
                        'is_cover' => $urutan === 0,
                        'urutan' => $urutan,
                    ]);

                    $written[] = $filename;
                    $urutan++;
                }

                if ($written === []) {
                    throw new RuntimeException('Gagal menyimpan gambar produk.');
                }
            });
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($written);

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

    /**
     * Simpan satu gambar, transkode ke WebP bila bisa. Null bila gagal (file dilewati).
     */
    private function storeImage(UploadedFile $file): ?string
    {
        try {
            return $this->storeAsWebp($file) ?? $file->store('products', 'public');
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Konversi gambar menjadi WebP (kualitas 80%) lalu simpan ke disk publik.
     */
    private function storeAsWebp(UploadedFile $file): ?string
    {
        $gdImage = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));

        if ($gdImage === false) {
            return null;
        }

        $filename = 'products/'.Str::random(40).'.webp';

        imagealphablending($gdImage, true);
        imagesavealpha($gdImage, true);

        ob_start();
        imagewebp($gdImage, null, 80);
        $webpData = ob_get_clean();
        imagedestroy($gdImage);

        Storage::disk('public')->put($filename, $webpData);

        return $filename;
    }

    /**
     * Kembalikan error sebagai JSON (AJAX) atau redirect balik ke form.
     */
    private function error(string $message, array $errors, StoreProductRequest $request): RedirectResponse|JsonResponse
    {
        if ($request->wantsJson()) {
            return response()->json(['message' => $message, 'errors' => $errors], 422);
        }

        return back()->withErrors($errors)->withInput();
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
