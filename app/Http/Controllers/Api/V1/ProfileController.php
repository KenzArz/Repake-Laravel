<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ReviewResource;
use App\Http\Resources\UserResource;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    /**
     * Profil sendiri + statistik ringkas.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        return ApiResponse::success([
            'user' => new UserResource($user),
            'stats' => $this->statsFor($user),
        ]);
    }

    /**
     * Katalog produk milik sendiri.
     */
    public function products(Request $request): JsonResponse
    {
        $user = $request->user();
        $status = (string) $request->query('status', 'semua');
        $sort = (string) $request->query('sort', 'terbaru');

        $query = Product::query()
            ->with(['primaryImage', 'user', 'category'])
            ->withExists(['favorites as is_favorite' => fn ($q) => $q->where('user_id', $user->id)])
            ->where('user_id', $user->id);

        if ($status === 'dijual') {
            $query->where('status', Product::STATUS_DIJUAL);
        } elseif ($status === 'terjual') {
            $query->where('status', Product::STATUS_TERJUAL);
        }

        $paginated = $query->paginate(20);

        return ApiResponse::success([
            'products' => ProductResource::collection($paginated->items()),
        ], meta: $this->metaFor($paginated));
    }

    /**
     * Ulasan yang diterima user ini.
     */
    public function reviews(Request $request): JsonResponse
    {
        $paginated = Review::query()
            ->with('product.primaryImage', 'reviewer')
            ->where('reviewee_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return ApiResponse::success(
            ReviewResource::collection($paginated->items()),
            meta: $this->metaFor($paginated),
        );
    }

    /**
     * Profil publik penjual lain.
     */
    public function seller(Request $request, string $key): JsonResponse
    {
        $user = $this->findSeller($request, $key);
        abort_if($user->is_banned, 404);

        $products = Product::query()
            ->with(['primaryImage', 'user', 'category'])
            ->where('user_id', $user->id)
            ->whereIn('status', [Product::STATUS_DIJUAL, Product::STATUS_TERJUAL])
            ->latest()
            ->get();

        $reviews = Review::query()
            ->with('product.primaryImage', 'reviewer')
            ->where('reviewee_id', $user->id)
            ->latest()
            ->take(20)
            ->get();

        return ApiResponse::success([
            'user' => new UserResource($user),
            'stats' => $this->statsFor($user),
            'products' => ProductResource::collection($products),
            'reviews' => ReviewResource::collection($reviews),
        ]);
    }

    /**
     * Update profil sendiri.
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $user->name = trim($validated['name']);
        $user->username = filled($validated['username'] ?? null)
            ? ltrim(trim((string) $validated['username']), '@')
            : null;
        $user->phone = ! empty($validated['phone']) ? trim($validated['phone']) : null;
        $user->kota = ! empty($validated['kota']) ? trim($validated['kota']) : 'Karawang';
        $user->bio = ! empty($validated['bio']) ? trim($validated['bio']) : null;

        if ($request->hasFile('avatar')) {
            $user->avatar = $request->file('avatar')->store('avatars', 'public');
        } elseif ($request->filled('remove_avatar') && $request->input('remove_avatar') == '1') {
            $user->avatar = null;
        }

        $user->save();

        return ApiResponse::success(new UserResource($user), 'Profil berhasil diperbarui.');
    }

    /**
     * Simpan kota hasil deteksi lokasi.
     */
    public function location(Request $request): JsonResponse
    {
        $data = $request->validate(['kota' => ['required', 'string', 'max:100']]);
        $request->user()->update(['kota' => $data['kota']]);

        return ApiResponse::success(['kota' => $data['kota']], 'Lokasi berhasil disimpan.');
    }

    private function findSeller(Request $request, string $key): User
    {
        $id = $request->query('id');

        if (filled($id)) {
            abort_unless(is_string($id) && Str::isUuid($id), 404);
            $user = User::query()->find($id);
        } else {
            $user = User::query()->where('username', $key)->first()
                ?? (Str::isUuid($key) ? User::query()->find($key) : null);
        }

        abort_if($user === null, 404);

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function statsFor(User $user): array
    {
        $rating = Review::query()
            ->where('reviewee_id', $user->id)
            ->selectRaw('COUNT(*) as total, COALESCE(AVG(rating), 0) as avg_rating')
            ->first();

        return [
            'rating' => round((float) $rating->avg_rating, 1),
            'reviews_count' => (int) $rating->total,
            'sold_count' => Product::query()->where('user_id', $user->id)->where('status', Product::STATUS_TERJUAL)->count(),
            'products_count' => Product::query()->where('user_id', $user->id)->count(),
            'favorites_count' => Product::query()
                ->dijual()
                ->whereHas('favorites', fn ($q) => $q->where('user_id', $user->id))
                ->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function metaFor(LengthAwarePaginator $paginated): array
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
