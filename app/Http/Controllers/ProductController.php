<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Models\Category;
use App\Models\CodArea;
use App\Models\Product;
use App\Services\ProductImageStorage;
use App\Services\ProductPublisher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    /**
     * Koordinat referensi & alias kelurahan/kecamatan untuk lokasi populer.
     *
     * @var array<string, array{lat: float, lng: float, keywords: array<int, string>}>
     */
    private const LOCATION_PRESETS = [
        'kampus 1 unsika' => [
            'lat' => -6.3236,
            'lng' => 107.3065,
            'keywords' => ['unsika', 'telukjambe', 'sukaharja', 'ronggowaluyo', 'karawang'],
        ],
        'kampus 2 unsika' => [
            'lat' => -6.3298,
            'lng' => 107.3112,
            'keywords' => ['unsika', 'telukjambe', 'puseurjaya', 'sukaharja', 'karawang'],
        ],
        'galuh mas' => [
            'lat' => -6.3269,
            'lng' => 107.2896,
            'keywords' => ['galuh mas', 'sukaharja', 'telukjambe', 'karawang'],
        ],
        'telukjambe' => [
            'lat' => -6.3250,
            'lng' => 107.3010,
            'keywords' => ['telukjambe', 'sukaharja', 'puseurjaya', 'sirnabaya', 'karawang'],
        ],
        'klari' => [
            'lat' => -6.3489,
            'lng' => 107.3514,
            'keywords' => ['klari', 'kosambi', 'gintungkerta', 'karawang'],
        ],
        'karawang' => [
            'lat' => -6.3023,
            'lng' => 107.2952,
            'keywords' => ['karawang', 'adiarsa', 'nagasari', 'tanjungpura', 'telukjambe'],
        ],
        'bekasi' => [
            'lat' => -6.2383,
            'lng' => 106.9756,
            'keywords' => ['bekasi', 'tambun', 'cikarang'],
        ],
    ];

    public function __construct(
        private readonly ProductImageStorage $images,
        private readonly ProductPublisher $publisher,
    ) {}

    /**
     * Endpoint JSON untuk rekomendasi pencarian di navbar (Desktop & Mobile).
     * Mengembalikan kata kunci menuju halaman pencarian (bukan halaman detail produk).
     */
    public function suggestions(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json([
                'categories' => [],
                'products' => [],
            ]);
        }

        $categories = Category::urut()
            ->where('name', 'like', "%{$q}%")
            ->take(3)
            ->get()
            ->map(fn(Category $cat): array => [
                'name' => $cat->name,
                'slug' => $cat->slug,
                'url' => route('products.index', ['kategori' => [$cat->slug]]),
            ])
            ->values();

        $products = Product::with('category')
            ->dijual()
            ->cari($q)
            ->latest()
            ->take(6)
            ->get()
            ->map(fn(Product $product): array => [
                'id' => $product->id,
                'judul' => $product->judul,
                'harga' => 'Rp ' . number_format((int) $product->harga, 0, ',', '.'),
                'kota' => $product->kota ?: 'Sekitar Kampus',
                'kondisi' => $product->kondisi,
                'category' => $product->category?->name ?? 'Produk',
                'search_url' => route('products.index', ['q' => $product->judul]),
            ])
            ->values();

        return response()->json([
            'categories' => $categories,
            'products' => $products,
        ]);
    }

    public function index(Request $request): View|string
    {
        $q = trim((string) $request->query('q', ''));
        $selectedKategori = array_values(array_filter((array) $request->query('kategori', [])));
        $selectedKondisi = array_values(array_filter((array) $request->query('kondisi', [])));
        $minPrice = $request->query('min_price');
        $maxPrice = $request->query('max_price');
        $radiusKm = is_numeric($request->query('radius')) ? (float) $request->query('radius') : null;
        $negoOnly = $request->boolean('nego');

        // Resolve & sinkronkan lokasi dari query parameter atau session navbar
        $userLocation = $this->resolveUserLocation($request);
        $activeKota = $userLocation['kota'] ?? '';
        $targetLat = $userLocation['lat'];
        $targetLng = $userLocation['lng'];

        $defaultSort = 'terbaru';
        $sort = (string) $request->query('sort', $defaultSort);

        $locationTarget = ($activeKota !== '' || $targetLat !== null)
            ? $this->resolveLocationTarget($userLocation)
            : ['lat' => null, 'lng' => null, 'keywords' => []];

        $targetLat = $locationTarget['lat'];
        $targetLng = $locationTarget['lng'];
        $locationKeywords = $locationTarget['keywords'];

        $query = Product::with(['primaryImage', 'user', 'category', 'codAreas'])
            ->dijual()
            ->when($q !== '', fn(Builder $qb) => $qb->cari($q))
            ->when($selectedKategori !== [], function (Builder $qb) use ($selectedKategori): void {
                $qb->whereHas('category', fn(Builder $cq) => $cq->whereIn('slug', $selectedKategori));
            })
            ->when($selectedKondisi !== [], function (Builder $qb) use ($selectedKondisi): void {
                $lowered = array_map('mb_strtolower', $selectedKondisi);
                $qb->whereIn(DB::raw('LOWER(kondisi)'), $lowered);
            })
            ->when($minPrice !== null && $minPrice !== '' && is_numeric($minPrice), fn(Builder $qb) => $qb->where('harga', '>=', (int) $minPrice))
            ->when($maxPrice !== null && $maxPrice !== '' && is_numeric($maxPrice), fn(Builder $qb) => $qb->where('harga', '<=', (int) $maxPrice))
            ->when($negoOnly, fn(Builder $qb) => $qb->where('is_negotiable', true));

        // Jika lokasi aktif atau urutkan terdekat dipilih
        if ($activeKota !== '' || $radiusKm !== null || ($sort === 'terdekat' && $targetLat !== null && $targetLng !== null)) {
            $maxRadius = $radiusKm ?? 15.0;

            $all = $query->latest()->get()->map(function (Product $product) use ($targetLat, $targetLng): Product {
                $product->distance_km = ($targetLat !== null && $targetLng !== null)
                    ? $this->calculateDistanceKm($product, $targetLat, $targetLng)
                    : null;

                return $product;
            });

            if ($activeKota !== '' || $radiusKm !== null) {
                $all = $all->filter(function (Product $p) use ($locationKeywords, $maxRadius): bool {
                    $keywordMatch = $this->matchesLocationKeywords($p, $locationKeywords);
                    $distanceMatch = $p->distance_km !== null && $p->distance_km <= $maxRadius;

                    return $keywordMatch || $distanceMatch;
                });
            }

            $sorted = match ($sort) {
                'termurah' => $all->sortBy('harga'),
                'termahal' => $all->sortByDesc('harga'),
                'terdekat' => $all->sortBy(fn(Product $p): array => [
                    $p->distance_km ?? 9999.0,
                    - ($p->created_at?->timestamp ?? 0),
                ]),
                default => $all->sortByDesc('created_at'),
            };

            $products = $this->paginateCollection($sorted->values(), 12, $request);
        } else {
            match ($sort) {
                'termurah' => $query->orderBy('harga', 'asc')->latest(),
                'termahal' => $query->orderBy('harga', 'desc')->latest(),
                default => $query->latest(),
            };

            $products = $query->paginate(12)->withQueryString();
        }

        if ($request->ajax()) {
            return view('products.partials.results', compact('products'))->render();
        }

        $categories = Category::urut()->get();
        $kondisiOptions = [
            'Baru',
            'Seperti baru',
            'Dirawat dengan baik',
            'Layak pakai',
        ];

        return view('products.index', compact(
            'products',
            'categories',
            'kondisiOptions',
            'q',
            'selectedKategori',
            'selectedKondisi',
            'activeKota',
            'sort',
            'defaultSort',
            'targetLat',
            'targetLng',
        ));
    }

    /**
     * @return array{kota: ?string, lat: ?float, lng: ?float}
     */
    private function resolveUserLocation(Request $request): array
    {
        if ($request->has('kota')) {
            $rawCity = trim((string) $request->query('kota', ''));

            if ($rawCity === '' || strcasecmp($rawCity, 'Pilih Lokasimu') === 0) {
                session()->forget('user_location');

                return ['kota' => null, 'lat' => null, 'lng' => null];
            }

            $lat = is_numeric($request->query('lat')) ? (float) $request->query('lat') : null;
            $lng = is_numeric($request->query('lng')) ? (float) $request->query('lng') : null;

            $saved = ['kota' => $rawCity, 'lat' => $lat, 'lng' => $lng];
            session()->put('user_location', $saved);

            return $saved;
        }

        if (session()->has('user_location')) {
            return session('user_location');
        }

        $fallbackCity = $request->user()?->kota ?? session('user_profile.kota');
        $fallbackCity = is_string($fallbackCity) ? trim($fallbackCity) : '';

        if ($fallbackCity === '' || strcasecmp($fallbackCity, 'Pilih Lokasimu') === 0) {
            return ['kota' => null, 'lat' => null, 'lng' => null];
        }

        return ['kota' => $fallbackCity, 'lat' => null, 'lng' => null];
    }

    /**
     * @param  array{kota: ?string, lat: ?float, lng: ?float}  $location
     * @return array{lat: ?float, lng: ?float, keywords: array<int, string>}
     */
    private function resolveLocationTarget(array $location): array
    {
        $city = (string) ($location['kota'] ?? '');
        $normalized = mb_strtolower($city);
        $keywords = $normalized !== '' ? [$normalized] : [];

        if (preg_match('/\(([^)]+)\)/', $normalized, $matches)) {
            $inside = trim($matches[1]);
            if ($inside !== '') {
                $keywords[] = $inside;
            }
        }

        $withoutParens = trim((string) preg_replace('/\([^)]*\)/', '', $normalized));
        if ($withoutParens !== '') {
            $keywords[] = $withoutParens;
        }

        // Pecah jika label berformat "Kecamatan, Kabupaten/Kota" dari OpenStreetMap
        foreach (explode(',', $withoutParens) as $part) {
            $clean = trim(str_replace(['kabupaten ', 'kota '], '', trim($part)));
            if ($clean !== '') {
                $keywords[] = $clean;
            }
        }

        $lat = $location['lat'];
        $lng = $location['lng'];

        foreach (self::LOCATION_PRESETS as $key => $preset) {
            if (str_contains($normalized, $key) || in_array($withoutParens, $preset['keywords'], true)) {
                $lat ??= $preset['lat'];
                $lng ??= $preset['lng'];
                $keywords = array_merge($keywords, $preset['keywords']);
                break;
            }
        }

        return [
            'lat' => $lat,
            'lng' => $lng,
            'keywords' => array_values(array_unique(array_filter($keywords))),
        ];
    }

    /**
     * @param  array<int, string>  $keywords
     */
    private function matchesLocationKeywords(Product $product, array $keywords): bool
    {
        $codAreaNames = $product->codAreas->pluck('name')->implode(' ');
        $haystack = mb_strtolower(trim("{$product->kota} {$product->patokan_lokasi} {$codAreaNames}"));

        foreach ($keywords as $word) {
            if ($word !== '' && str_contains($haystack, $word)) {
                return true;
            }
        }

        return false;
    }

    private function calculateDistanceKm(Product $product, float $targetLat, float $targetLng): ?float
    {
        $coords = data_get($product->codAreas->first(), 'boundary.coordinates', []);

        if (! is_array($coords) || count($coords) < 2) {
            return null;
        }

        $prodLng = (float) $coords[0];
        $prodLat = (float) $coords[1];

        if ($prodLat === 0.0 && $prodLng === 0.0) {
            return null;
        }

        $earthRadiusKm = 6371.0;
        $dLat = deg2rad($prodLat - $targetLat);
        $dLng = deg2rad($prodLng - $targetLng);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($targetLat)) * cos(deg2rad($prodLat)) * sin($dLng / 2) ** 2;

        return round($earthRadiusKm * (2 * atan2(sqrt($a), sqrt(1 - $a))), 1);
    }

    private function paginateCollection(Collection $items, int $perPage, Request $request): LengthAwarePaginator
    {
        $page = max(1, (int) $request->query('page', 1));
        $sliced = $items->slice(($page - 1) * $perPage, $perPage)->values();

        return (new LengthAwarePaginator(
            $sliced,
            $items->count(),
            $perPage,
            $page,
            ['path' => $request->url()]
        ))->withQueryString();
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
        } catch (\Throwable $e) {
            report($e);

            return $this->error(
                'Gagal mengunggah produk. Silakan coba lagi atau gunakan gambar lain.',
                ['images' => ['Gagal menyimpan gambar produk. Coba lagi.']],
                $request,
            );
        }

        // Titik COD bukan bagian dari transaksi pembuatan produk. Bila gagal,
        // produk tetap tersimpan dan penjual bisa melengkapi lokasinya lewat edit.
        if ($request->filled(['cod_lat', 'cod_lng'])) {
            try {
                $placeName = $request->cod_place_name ?: $request->kota;

                $codArea = CodArea::firstOrCreate(
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
            } catch (\Throwable $e) {
                report($e);
            }
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

    private function error(string $message, array $errors, Request $request): RedirectResponse|JsonResponse
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
        abort_if($product->user_id !== Auth::id(), 403);

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
        abort_if($product->user_id !== Auth::id(), 403);

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
            'images' => ['nullable', 'array', 'max:' . ProductImageStorage::MAX_IMAGES],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ], [
            'images.*.mimes' => 'Format gambar harus JPG, PNG, atau WebP.',
            'images.*.max' => 'Ukuran tiap gambar maksimal 5MB.',
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

        $storedPaths = [];
        $deletedPaths = [];

        try {
            DB::transaction(function () use ($product, $validated, $request, $deleteIds, $newFiles, &$storedPaths, &$deletedPaths): void {
                $product->update([
                    'judul' => $validated['judul'],
                    'category_id' => $validated['category_id'],
                    'harga' => (int) $validated['harga'],
                    'kondisi' => $validated['kondisi'],
                    'status' => $product->status === Product::STATUS_DITANGGUIHKAN
                        ? Product::STATUS_DITANGGUIHKAN
                        : ($validated['status'] ?? $product->status),
                    'deskripsi' => $validated['deskripsi'],
                    'kota' => $validated['kota'],
                    'patokan_lokasi' => $validated['patokan_lokasi'] ?? null,
                    'is_negotiable' => $request->boolean('is_negotiable', $product->is_negotiable),
                ]);

                // Update titik lokasi COD jika tersedia
                if ($request->filled(['cod_lat', 'cod_lng'])) {
                    $placeName = ($validated['cod_place_name'] ?? null) ?: $validated['kota'];

                    $codArea = CodArea::firstOrCreate(
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

                // Hapus baris foto lama. File fisiknya baru dihapus setelah
                // transaksi sukses supaya tidak ada foto yang hilang saat gagal.
                if ($deleteIds !== []) {
                    $toDelete = $product->images()->whereIn('id', $deleteIds)->get();
                    foreach ($toDelete as $img) {
                        $deletedPaths[] = preg_replace('#^/?storage/#', '', $img->path) ?? $img->path;
                        $img->delete();
                    }
                }

                // Simpan foto baru yang diunggah
                $nextUrutan = ((int) $product->images()->max('urutan')) + 1;
                $savedNewImages = [];

                foreach ($newFiles as $file) {
                    $storedPath = $this->images->storeFile($file);

                    if ($storedPath === null) {
                        throw new \RuntimeException('Gagal menyimpan gambar produk.');
                    }

                    $storedPaths[] = $storedPath;

                    $savedNewImages[] = $product->images()->create([
                        'path' => $storedPath,
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
            });
        } catch (\Throwable $e) {
            $this->images->delete($storedPaths);
            report($e);

            return $this->error(
                'Gagal menyimpan perubahan produk. Silakan coba lagi.',
                ['images' => ['Gagal menyimpan perubahan produk. Coba lagi.']],
                $request,
            );
        }

        $this->images->delete($deletedPaths);

        $toastMessage = "Perubahan pada produk \"{$product->judul}\" berhasil disimpan.";

        if ($request->wantsJson()) {
            session()->flash('success', $toastMessage);

            return response()->json(['redirect' => $product->detailUrl()]);
        }

        return redirect()
            ->to($product->detailUrl())
            ->with('success', $toastMessage);
    }

    /**
     * Hapus produk (soft delete).
     */
    public function destroy(Product $product): RedirectResponse
    {
        abort_if($product->user_id !== Auth::id(), 403);

        $judul = $product->judul;
        $detailUrl = $product->detailUrl();
        $product->delete();

        $message = "Produk \"{$judul}\" berhasil dihapus dari katalog.";

        if (url()->previous() === $detailUrl) {
            return redirect()
                ->route('profile.catalog')
                ->with('success', $message);
        }

        return back()->with('success', $message);
    }
}
