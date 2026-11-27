<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\StoreReportRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductDetailResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ReviewResource;
use App\Models\Category;
use App\Models\CodArea;
use App\Models\Order;
use App\Models\Product;
use App\Models\Report;
use App\Models\Review;
use App\Services\ProductImageStorage;
use App\Services\ProductLocationService;
use App\Services\ProductPublisher;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    private const DEFAULT_PER_PAGE = 20;

    private const MAX_PER_PAGE = 50;

    public function __construct(
        private readonly ProductImageStorage $images,
        private readonly ProductPublisher $publisher,
        private readonly ProductLocationService $location,
    ) {}

    /**
     * Listing produk dengan filter.
     */
    public function index(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $selectedKategori = array_values(array_filter((array) $request->query('kategori', [])));
        $selectedKondisi = array_values(array_filter((array) $request->query('kondisi', [])));
        $minPrice = $request->query('min_price');
        $maxPrice = $request->query('max_price');
        $radiusKm = is_numeric($request->query('radius')) ? (float) $request->query('radius') : null;
        $negoOnly = filter_var($request->query('nego'), FILTER_VALIDATE_BOOL);
        $sort = in_array((string) $request->query('sort', 'terbaru'), ['terbaru', 'termurah', 'termahal', 'terdekat'], true)
            ? (string) $request->query('sort', 'terbaru')
            : 'terbaru';

        $kota = $request->query('kota');
        $lat = is_numeric($request->query('lat')) ? (float) $request->query('lat') : null;
        $lng = is_numeric($request->query('lng')) ? (float) $request->query('lng') : null;

        $query = Product::query()
            ->with(['primaryImage', 'user', 'category'])
            ->dijual()
            ->when($q !== '', fn (Builder $qb) => $qb->cari($q))
            ->when($selectedKategori !== [], function (Builder $qb) use ($selectedKategori): void {
                $qb->whereHas('category', fn (Builder $cq) => $cq->whereIn('slug', $selectedKategori));
            })
            ->when($selectedKondisi !== [], function (Builder $qb) use ($selectedKondisi): void {
                $lowered = array_map('mb_strtolower', $selectedKondisi);
                $qb->whereIn(DB::raw('LOWER(kondisi)'), $lowered);
            })
            ->when($minPrice !== null && $minPrice !== '' && is_numeric($minPrice), fn (Builder $qb) => $qb->where('harga', '>=', (int) $minPrice))
            ->when($maxPrice !== null && $maxPrice !== '' && is_numeric($maxPrice), fn (Builder $qb) => $qb->where('harga', '<=', (int) $maxPrice))
            ->when($negoOnly, fn (Builder $qb) => $qb->where('is_negotiable', true));

        $user = $request->user();
        if ($user !== null) {
            $query->withExists(['favorites as is_favorite' => fn ($qb) => $qb->where('user_id', $user->id)]);
        }

        $perPage = $this->perPage($request);
        $page = max(1, (int) $request->query('page', 1));

        $target = $this->location->resolveTarget($kota, $lat, $lng);
        $hasLocation = ($kota !== null && $kota !== '') || $lat !== null || $lng !== null;
        $useCollection = $hasLocation || $radiusKm !== null || $sort === 'terdekat' || $sort === 'termurah' || $sort === 'termahal';

        if ($useCollection) {
            $all = (clone $query)->with('codAreas')->latest()->get()->map(function (Product $product) use ($target): Product {
                $product->distance_km = $this->location->distanceKm($product, $target['lat'], $target['lng']);

                return $product;
            });

            if ($hasLocation || $radiusKm !== null) {
                $maxRadius = $radiusKm ?? 15.0;
                $all = $all->filter(function (Product $p) use ($target, $maxRadius): bool {
                    return $this->location->matchesKeywords($p, $target['keywords'])
                        || ($p->distance_km !== null && $p->distance_km <= $maxRadius);
                })->values();
            }

            $all = match ($sort) {
                'termurah' => $all->sortBy('harga')->values(),
                'termahal' => $all->sortByDesc('harga')->values(),
                'terdekat' => $all->sortBy(fn (Product $p): array => [
                    $p->distance_km ?? 9999.0,
                    -($p->created_at?->timestamp ?? 0),
                ])->values(),
                default => $all->sortByDesc('created_at')->values(),
            };

            $paginated = $this->location->paginate($all, $perPage, $page, $request->url());
        } else {
            match ($sort) {
                'termurah' => $query->orderBy('harga', 'asc')->latest(),
                'termahal' => $query->orderBy('harga', 'desc')->latest(),
                default => $query->latest(),
            };
            $paginated = $query->paginate($perPage, page: $page);
        }

        return ApiResponse::success(
            ProductResource::collection($paginated->items()),
            meta: $this->pageMeta($paginated),
        );
    }

    /**
     * Rekomendasi pencarian (kategori + produk).
     */
    public function suggestions(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        if (mb_strlen($q) < 2) {
            return ApiResponse::success(['categories' => [], 'products' => []]);
        }

        $categories = Category::urut()
            ->where('name', 'like', "%{$q}%")
            ->take(3)
            ->get();

        $products = Product::query()
            ->with('category')
            ->dijual()
            ->cari($q)
            ->latest()
            ->take(6)
            ->get()
            ->map(fn (Product $product): array => [
                'id' => $product->id,
                'judul' => $product->judul,
                'harga' => 'Rp '.number_format((int) $product->harga, 0, ',', '.'),
                'kota' => $product->kota ?: 'Sekitar Kampus',
                'kondisi' => $product->kondisi,
                'category' => $product->category?->name ?? 'Produk',
            ])
            ->values();

        return ApiResponse::success([
            'categories' => CategoryResource::collection($categories),
            'products' => $products,
        ]);
    }

    /**
     * Detail produk.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        abort_unless(Str::isUuid($id), 404);

        $baseQuery = Product::query()
            ->with(['images', 'primaryImage', 'user', 'codAreas', 'category'])
            ->withCount('favorites');

        $product = (clone $baseQuery)->find($id);
        abort_if($product === null, 404);

        $viewer = $request->user();
        $isOwner = $viewer !== null && $viewer->id === $product->user_id;
        $isAdmin = $viewer?->isAdmin() ?? false;

        $hasOrder = $viewer !== null && Order::query()
            ->where('product_id', $product->id)
            ->where('buyer_id', $viewer->id)
            ->exists();

        $hiddenStatuses = [Product::STATUS_DRAFT, Product::STATUS_DITANGGUIHKAN, Product::STATUS_DITINJAU, Product::STATUS_TERJUAL];
        abort_if(! $isAdmin && ! $isOwner && ! $hasOrder && in_array($product->status, $hiddenStatuses, true), 404);
        abort_if(! $isAdmin && ! $hasOrder && $product->user?->is_banned, 404);

        if ($viewer !== null) {
            $product->is_favorite = $product->favorites()->where('user_id', $viewer->id)->exists();
        }

        // Ulasan produk ini (pagination) + statistik rating penjual.
        $reviewsQuery = Review::query()
            ->with('reviewer')
            ->where('product_id', $product->id)
            ->latest();

        $reviews = $reviewsQuery->get();

        $stats = Review::query()
            ->where('reviewee_id', $product->user_id)
            ->selectRaw('COUNT(*) as total, COALESCE(AVG(rating), 0) as avg_rating')
            ->first();

        $soldCount = Order::query()
            ->where('seller_id', $product->user_id)
            ->where('status', Order::STATUS_SELESAI)
            ->count();

        $product->seller_stats = [
            'rating' => round((float) $stats->avg_rating, 1),
            'reviews_count' => (int) $stats->total,
            'sold_count' => $soldCount,
        ];

        // Produk serupa dari kategori sama.
        $product->setRelation('similar', Product::query()
            ->with(['primaryImage', 'user'])
            ->when($product->category_id, fn ($q) => $q->where('category_id', $product->category_id))
            ->whereKeyNot($product->getKey())
            ->dijual()
            ->latest()
            ->take(4)
            ->get());

        $viewerOrder = $viewer === null
            ? null
            : Order::query()
                ->where('product_id', $product->id)
                ->where('buyer_id', $viewer->id)
                ->where('status', Order::STATUS_SELESAI)
                ->latest('id')
                ->first();

        $viewerReview = $viewer !== null
            ? $reviews->first(fn (Review $review): bool => $review->reviewer_id === $viewer->id)
            : null;

        $product->setRelation('reviews', $reviews->take(20)->values());
        $product->viewer_is_owner = $isOwner;
        $product->viewer_has_order = $hasOrder;
        $product->viewer_can_review = $viewer !== null
            && ! $isOwner
            && $viewerReview === null
            && $viewerOrder !== null;

        return ApiResponse::success(new ProductDetailResource($product));
    }

    /**
     * Ulasan produk (pagination DEDIKASI).
     */
    public function reviews(Request $request, string $id): JsonResponse
    {
        abort_unless(Str::isUuid($id), 404);
        $product = Product::query()->findOrFail($id);

        $paginated = Review::query()
            ->with('reviewer')
            ->where('product_id', $product->id)
            ->latest()
            ->paginate($this->perPage($request));

        return ApiResponse::success(
            ReviewResource::collection($paginated->items()),
            meta: $this->pageMeta($paginated),
        );
    }

    /**
     * Buat produk baru.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $files = $this->images->validFiles($request->file('images'));

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

            return ApiResponse::error('Gagal mengunggah produk. Silakan coba lagi.', 422, ['images' => ['Gagal menyimpan gambar produk.']]);
        }

        $this->syncCodArea($product, $request);

        return ApiResponse::created(new ProductDetailResource($product), 'Produk berhasil dipasang.');
    }

    /**
     * Update produk milik penjual.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        abort_unless(Str::isUuid($id), 404);
        $product = Product::query()->findOrFail($id);
        abort_if($product->user_id !== Auth::id(), 403);

        $validated = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'harga' => ['required', 'integer', 'min:0'],
            'kondisi' => ['required', 'string', 'max:50'],
            'status' => ['sometimes', Rule::in([Product::STATUS_DRAFT, Product::STATUS_DIJUAL, Product::STATUS_TERJUAL])],
            'deskripsi' => ['required', 'string'],
            'kota' => ['required', 'string', 'max:100'],
            'cod_place_name' => ['nullable', 'string', 'max:150'],
            'province' => ['nullable', 'string', 'max:100'],
            'cod_lat' => ['nullable', 'numeric'],
            'cod_lng' => ['nullable', 'numeric'],
            'patokan_lokasi' => ['nullable', 'string', 'max:255'],
            'is_negotiable' => ['sometimes', 'boolean'],
            'images' => ['nullable', 'array', 'max:'.ProductImageStorage::MAX_IMAGES],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'delete_images' => ['nullable', 'array'],
            'delete_images.*' => ['integer'],
            'cover_image_id' => ['nullable', 'integer'],
        ], [
            'images.*.mimes' => 'Format gambar harus JPG, PNG, atau WebP.',
            'images.*.max' => 'Ukuran tiap gambar maksimal 5MB.',
        ]);

        $newFiles = $this->images->validFiles($request->file('images'));
        $deleteIds = collect($validated['delete_images'] ?? [])->map(fn ($id) => (int) $id)->all();

        $product->load('images');
        $remainingExisting = $product->images->whereNotIn('id', $deleteIds);
        $totalPhotos = $remainingExisting->count() + $newFiles->count();

        if ($totalPhotos < 1) {
            return ApiResponse::error('Produk wajib memiliki minimal 1 gambar.', 422, ['images' => ['Produk wajib memiliki minimal 1 gambar.']]);
        }
        if ($totalPhotos > ProductImageStorage::MAX_IMAGES) {
            return ApiResponse::error('Maksimal total gambar produk adalah '.ProductImageStorage::MAX_IMAGES.' gambar.', 422, ['images' => ['Terlalu banyak gambar.']]);
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

                if ($deleteIds !== []) {
                    $toDelete = $product->images()->whereIn('id', $deleteIds)->get();
                    foreach ($toDelete as $img) {
                        $deletedPaths[] = preg_replace('#^/?storage/#', '', $img->path) ?? $img->path;
                        $img->delete();
                    }
                }

                $nextUrutan = ((int) $product->images()->max('urutan')) + 1;
                foreach ($newFiles as $file) {
                    $path = $this->images->storeFile($file);
                    if ($path === null) {
                        throw new \RuntimeException('Gagal menyimpan gambar produk.');
                    }
                    $storedPaths[] = $path;
                    $product->images()->create(['path' => $path, 'urutan' => $nextUrutan++, 'is_cover' => false]);
                }

                if (! $product->images()->where('is_cover', true)->exists()) {
                    $first = $product->images()->orderBy('urutan')->first();
                    if ($first) {
                        $product->images()->whereKey($first->id)->update(['is_cover' => true, 'urutan' => 0]);
                    }
                }
            });
        } catch (\Throwable $e) {
            $this->images->delete($storedPaths);
            report($e);

            return ApiResponse::error('Gagal menyimpan perubahan produk. Silakan coba lagi.', 422, ['images' => ['Gagal menyimpan gambar.']]);
        }

        $this->images->delete($deletedPaths);

        // Set cover eksplisit bila diminta.
        if (! empty($validated['cover_image_id']) && $product->images()->whereKey((int) $validated['cover_image_id'])->exists()) {
            $product->images()->update(['is_cover' => false]);
            $product->images()->whereKey((int) $validated['cover_image_id'])->update(['is_cover' => true]);
        }

        $this->syncCodArea($product, $request);
        $product->load(['images', 'primaryImage', 'user', 'category']);

        return ApiResponse::success(new ProductDetailResource($product), 'Perubahan produk berhasil disimpan.');
    }

    /**
     * Soft delete produk.
     */
    public function destroy(string $id): JsonResponse
    {
        abort_unless(Str::isUuid($id), 404);
        $product = Product::query()->findOrFail($id);
        abort_if($product->user_id !== Auth::id(), 403);

        $product->delete();

        return ApiResponse::success(null, 'Produk berhasil dihapus dari katalog.');
    }

    /**
     * Lapor produk.
     */
    public function report(StoreReportRequest $request, string $id): JsonResponse
    {
        abort_unless(Str::isUuid($id), 404);
        $product = Product::query()->findOrFail($id);
        abort_if($product->user_id === Auth::id(), 403);

        $validated = $request->validated();

        Report::create([
            'product_id' => $product->id,
            'reporter_id' => Auth::id(),
            'alasan' => $validated['alasan'],
            'keterangan' => $validated['keterangan'],
            'bukti' => $request->hasFile('bukti') ? $request->file('bukti')->store('reports', 'public') : null,
            'status' => Report::STATUS_PENDING,
        ]);

        return ApiResponse::success(null, 'Laporan Anda berhasil dikirim. Tim RePake akan segera meninjau barang ini.');
    }

    private function syncCodArea(Product $product, Request $request): void
    {
        if (! $request->filled(['cod_lat', 'cod_lng'])) {
            return;
        }

        $placeName = $request->cod_place_name ?: $request->kota;

        $codArea = CodArea::firstOrCreate(
            ['name' => $placeName, 'city' => $request->kota],
            [
                'province' => $request->province ?? 'Jawa Barat',
                'boundary' => [
                    'type' => 'Point',
                    'coordinates' => [(float) $request->cod_lng, (float) $request->cod_lat],
                ],
            ],
        );

        $product->codAreas()->sync([$codArea->id]);
    }

    private function perPage(Request $request): int
    {
        $perPage = (int) $request->query('per_page', self::DEFAULT_PER_PAGE);

        return max(1, min(self::MAX_PER_PAGE, $perPage));
    }

    /**
     * @return array<string, mixed>
     */
    private function pageMeta(LengthAwarePaginator $paginated): array
    {
        return [
            'current_page' => $paginated->currentPage(),
            'per_page' => $paginated->perPage(),
            'total' => $paginated->total(),
            'last_page' => $paginated->lastPage(),
            'from' => $paginated->firstItem(),
            'to' => $paginated->lastItem(),
        ];
    }
}
