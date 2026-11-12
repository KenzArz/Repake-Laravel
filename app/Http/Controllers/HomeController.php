<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class HomeController extends Controller
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

    public function index(Request $request): View
    {
        $kategori = trim((string) $request->query('kategori', ''));
        $userLocation = $this->resolveUserLocation($request);

        $baseQuery = Product::with(['primaryImage', 'user'])
            ->dijual()
            ->when($kategori !== '', function (Builder $query) use ($kategori): void {
                $query->whereHas('category', fn (Builder $q) => $q->where('slug', $kategori));
            });

        $nearby = $this->getNearbyProducts(clone $baseQuery, $userLocation, 4);

        $latest = (clone $baseQuery)
            ->latest()
            ->take(8)
            ->get();

        $activeCategory = $kategori !== ''
            ? Category::where('slug', $kategori)->first()
            : null;

        return view('home.index', [
            'banners' => Banner::aktif()
                ->take(3)
                ->get()
                ->map(fn (Banner $banner): string => $banner->image_url)
                ->all(),
            'categories' => $this->categories(),
            'activeCategory' => $activeCategory,
            'nearby' => $nearby,
            'latest' => $latest,
        ]);
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
     * @param  array{kota: ?string, lat: ?float, lng: ?float}  $userLocation
     */
    private function getNearbyProducts(Builder $baseQuery, array $userLocation, int $limit = 4): Collection
    {
        if ($userLocation['kota'] === null && $userLocation['lat'] === null) {
            return $baseQuery->latest()->take($limit)->get();
        }

        $target = $this->resolveLocationTarget($userLocation);

        $candidates = $baseQuery
            ->with('codAreas')
            ->latest()
            ->get();

        return $candidates
            ->sortBy(function (Product $product) use ($target): array {
                $distanceKm = $this->calculateProductDistance($product, $target['lat'], $target['lng']);
                $isKeywordMatch = $this->matchesLocationKeywords($product, $target['keywords']);

                $isNearby = $isKeywordMatch || ($distanceKm !== null && $distanceKm <= 15.0);
                $tier = $isNearby ? 0 : 1;
                $sortDistance = $distanceKm ?? ($isKeywordMatch ? 5.0 : 9999.0);
                $newestFirst = -($product->created_at?->timestamp ?? 0);

                return [$tier, $sortDistance, $newestFirst];
            })
            ->take($limit)
            ->values();
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

    private function calculateProductDistance(Product $product, ?float $targetLat, ?float $targetLng): ?float
    {
        if ($targetLat === null || $targetLng === null) {
            return null;
        }

        $codArea = $product->codAreas->first();
        $coords = data_get($codArea, 'boundary.coordinates', []);

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

        return $earthRadiusKm * (2 * atan2(sqrt($a), sqrt(1 - $a)));
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

    private function categories(): Collection
    {
        return Category::urut()
            ->get()
            ->map(fn ($category): object => (object) [
                'name' => $category->name,
                'slug' => $category->slug,
                'icon_url' => $category->icon,
            ]);
    }
}
