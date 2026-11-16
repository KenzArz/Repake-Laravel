<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Logika lokasi & produk terdekat, dipakai bersama web dan API.
 *
 * Menggantikan helper private yang sebelumnya terduplikasi di
 * HomeController dan ProductController.
 */
class ProductLocationService
{
    /**
     * Koordinat referensi & alias kelurahan/kecamatan untuk lokasi populer.
     *
     * @var array<string, array{lat: float, lng: float, keywords: array<int, string>}>
     */
    private const LOCATION_PRESETS = [
        'kampus 1 unsika' => ['lat' => -6.3236, 'lng' => 107.3065, 'keywords' => ['unsika', 'telukjambe', 'sukaharja', 'ronggowaluyo', 'karawang']],
        'kampus 2 unsika' => ['lat' => -6.3298, 'lng' => 107.3112, 'keywords' => ['unsika', 'telukjambe', 'puseurjaya', 'sukaharja', 'karawang']],
        'galuh mas' => ['lat' => -6.3269, 'lng' => 107.2896, 'keywords' => ['galuh mas', 'sukaharja', 'telukjambe', 'karawang']],
        'telukjambe' => ['lat' => -6.3250, 'lng' => 107.3010, 'keywords' => ['telukjambe', 'sukaharja', 'puseurjaya', 'sirnabaya', 'karawang']],
        'klari' => ['lat' => -6.3489, 'lng' => 107.3514, 'keywords' => ['klari', 'kosambi', 'gintungkerta', 'karawang']],
        'karawang' => ['lat' => -6.3023, 'lng' => 107.2952, 'keywords' => ['karawang', 'adiarsa', 'nagasari', 'tanjungpura', 'telukjambe']],
        'bekasi' => ['lat' => -6.2383, 'lng' => 106.9756, 'keywords' => ['bekasi', 'tambun', 'cikarang']],
    ];

    /**
     * @param  string|null  $kota  Label kota mentah (mis. "Telukjambe, Kab. Karawang").
     * @return array{lat: ?float, lng: ?float, keywords: array<int, string>}
     */
    public function resolveTarget(?string $kota, ?float $lat, ?float $lng): array
    {
        $normalized = mb_strtolower(trim((string) $kota));
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

        foreach (explode(',', $withoutParens) as $part) {
            $clean = trim(str_replace(['kabupaten ', 'kota '], '', trim($part)));
            if ($clean !== '') {
                $keywords[] = $clean;
            }
        }

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
    public function matchesKeywords(Product $product, array $keywords): bool
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

    public function distanceKm(Product $product, ?float $targetLat, ?float $targetLng): ?float
    {
        if ($targetLat === null || $targetLng === null) {
            return null;
        }

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

    /**
     * Urutkan kandidat dengan memprioritaskan produk yang dekat atau cocok
     * kata kunci lokasi, lalu menurut jarak dan kebaruan.
     *
     * @param  Collection<int, Product>  $products
     * @param  array{lat: ?float, lng: ?float, keywords: array<int, string>}  $target
     * @return Collection<int, Product>
     */
    public function rankNearby(Collection $products, array $target, float $radiusKm = 15.0, int $limit = 4): Collection
    {
        $ranked = $products->map(function (Product $product) use ($target): array {
            $distanceKm = $this->distanceKm($product, $target['lat'], $target['lng']);
            $isKeywordMatch = $this->matchesKeywords($product, $target['keywords']);

            $isNearby = $isKeywordMatch || ($distanceKm !== null && $distanceKm <= 15.0);
            $tier = $isNearby ? 0 : 1;
            $sortDistance = $distanceKm ?? ($isKeywordMatch ? 5.0 : 9999.0);

            return [$tier, $sortDistance, -($product->created_at?->timestamp ?? 0), $product];
        });

        $sorted = $ranked->sortBy(fn (array $row): array => [$row[0], $row[1], $row[2]]);

        return $sorted->take($limit)->map(fn (array $row): Product => $row[3])->values();
    }

    /**
     * @param  Collection<int, Product>  $items
     */
    public function paginate(Collection $items, int $perPage, int $page, string $path): LengthAwarePaginator
    {
        $sliced = $items->slice(($page - 1) * $perPage, $perPage)->values();

        return (new LengthAwarePaginator(
            $sliced,
            $items->count(),
            $perPage,
            $page,
            ['path' => $path],
        ))->withQueryString();
    }
}
