<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BannerResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductLocationService;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __construct(private readonly ProductLocationService $location) {}

    public function index(Request $request): JsonResponse
    {
        $kategori = trim((string) $request->query('kategori', ''));

        $baseQuery = Product::query()
            ->with(['primaryImage', 'user', 'category'])
            ->dijual()
            ->when($kategori !== '', function (Builder $query) use ($kategori): void {
                $query->whereHas('category', fn (Builder $q) => $q->where('slug', $kategori));
            });

        $user = $request->user();
        if ($user !== null) {
            $baseQuery->withExists(['favorites as is_favorite' => fn ($q) => $q->where('user_id', $user->id)]);
        }

        $target = $this->location->resolveTarget(
            $request->query('kota'),
            $this->nullableFloat($request->query('lat')),
            $this->nullableFloat($request->query('lng')),
        );

        $nearbyCandidates = (clone $baseQuery)->with('codAreas')->latest()->get();
        $nearby = $this->location->rankNearby($nearbyCandidates, $target, 15.0, 4);

        $latest = (clone $baseQuery)->latest()->take(8)->get();

        return ApiResponse::success([
            'banners' => BannerResource::collection(Banner::aktif()->get()),
            'categories' => CategoryResource::collection(Category::urut()->get()),
            'conditions' => ['Baru', 'Seperti baru', 'Dirawat dengan baik', 'Layak pakai'],
            'nearby' => ProductResource::collection($nearby),
            'latest' => ProductResource::collection($latest),
            'location' => [
                'kota' => $request->query('kota'),
                'lat' => $target['lat'],
                'lng' => $target['lng'],
                'keywords' => $target['keywords'],
            ],
        ]);
    }

    private function nullableFloat(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }
}
