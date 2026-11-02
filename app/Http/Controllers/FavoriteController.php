<?php

namespace App\Http\Controllers;

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
        $favoriteIds = session()->get('favorites', []);

        // Filter produk yang ada di daftar favorit
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
        $favorites = session()->get('favorites', []);

        if (in_array($id, $favorites, true)) {
            // Hapus dari favorit
            $favorites = array_values(array_filter($favorites, fn ($favId): bool => (int) $favId !== $id));
            $isFavorite = false;
            $message = 'Produk dihapus dari favorit.';
        } else {
            // Tambahkan ke favorit
            $favorites[] = $id;
            $favorites = array_values(array_unique($favorites));
            $isFavorite = true;
            $message = 'Produk berhasil ditambahkan ke favorit.';
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

        return back()->with('status', $message);
    }
}
