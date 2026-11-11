<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    /**
     * Tampilkan halaman daftar produk favorit.
     */
    public function index(Request $request): View
    {
        $user = auth()->user();

        // 1. Coba ambil dari Database jika user terautentikasi
        if ($user) {
            $dbFavorites = Product::with(['user', 'primaryImage', 'images', 'codAreas'])
                ->whereHas('favorites', fn ($q) => $q->where('user_id', $user->id))
                ->get();

            if ($dbFavorites->isNotEmpty()) {
                return view('favorites.index', [
                    'favorites' => $dbFavorites,
                    'count' => $dbFavorites->count(),
                ]);
            }
        }

        // 2. Fallback ke session & dummy data
        $favoriteIds = session()->get('favorites', []);

        $favorites = Product::dummyCatalog()
            ->filter(fn (object $product): bool => in_array($product->id, $favoriteIds, true))
            ->values();

        return view('favorites.index', [
            'favorites' => $favorites,
            'count' => $favorites->count(),
        ]);
    }

    /**
     * Toggle status favorit untuk suatu produk.
     */
    public function toggle(Request $request, int|string $id): JsonResponse|RedirectResponse
    {
        $id = (int) $id;
        $user = auth()->user();
        $favorites = session()->get('favorites', []);

        $isFavorite = false;

        // Jika user terautentikasi dan produk ada di database
        if ($user) {
            $existing = Favorite::where('user_id', $user->id)->where('product_id', $id)->first();
            if ($existing) {
                $existing->delete();
                $isFavorite = false;
                $message = 'Produk dihapus dari favorit.';
            } else {
                Favorite::create([
                    'user_id' => $user->id,
                    'product_id' => $id,
                ]);
                $isFavorite = true;
                $message = 'Produk berhasil ditambahkan ke favorit.';
            }
        } else {
            // Berbasis session jika belum ada user DB
            if (in_array($id, $favorites, true)) {
                $favorites = array_values(array_filter($favorites, fn ($favId): bool => (int) $favId !== $id));
                $isFavorite = false;
                $message = 'Produk dihapus dari favorit.';
            } else {
                $favorites[] = $id;
                $favorites = array_values(array_unique($favorites));
                $isFavorite = true;
                $message = 'Produk berhasil ditambahkan ke favorit.';
            }
        }

        // Sinkronisasi session
        if ($isFavorite) {
            $favorites[] = $id;
            $favorites = array_values(array_unique($favorites));
        } else {
            $favorites = array_values(array_filter($favorites, fn ($favId): bool => (int) $favId !== $id));
        }
        session()->put('favorites', $favorites);

        if ($request->wantsJson() || $request->ajax() || $request->header('Accept') === 'application/json') {
            return response()->json([
                'success' => true,
                'is_favorite' => $isFavorite,
                'product_id' => $id,
                'count' => count($favorites),
                'message' => $message,
            ]);
        }

        return back()->with('favorite_status', $message);
    }
}
