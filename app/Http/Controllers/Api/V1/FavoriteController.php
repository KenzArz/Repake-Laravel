<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Favorite;
use App\Models\Product;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FavoriteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::query()
            ->with(['primaryImage', 'user', 'category'])
            ->dijual()
            ->whereHas('favorites', fn ($q) => $q->where('user_id', $request->user()->id))
            ->withExists(['favorites as is_favorite' => fn ($q) => $q->where('user_id', $request->user()->id)])
            ->latest();

        $products = $query->get();

        return ApiResponse::success([
            'products' => ProductResource::collection($products),
            'count' => $products->count(),
        ]);
    }

    public function toggle(Request $request, string $id): JsonResponse
    {
        abort_unless(Str::isUuid($id), 404);
        $product = Product::query()->findOrFail($id);

        $userId = $request->user()->id;
        $productId = $product->id;

        $existing = Favorite::where('user_id', $userId)->where('product_id', $productId)->first();

        if ($existing) {
            $existing->delete();
            $isFavorite = false;
            $message = 'Produk dihapus dari favorit.';
        } else {
            Favorite::create(['user_id' => $userId, 'product_id' => $productId]);
            $isFavorite = true;
            $message = 'Produk berhasil ditambahkan ke favorit.';
        }

        $count = Product::whereHas('favorites', fn ($q) => $q->where('user_id', $userId))->count();

        return ApiResponse::success([
            'is_favorite' => $isFavorite,
            'product_id' => $productId,
            'count' => $count,
        ], $message);
    }
}
