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
     * Tampilkan halaman daftar produk favorit dari database.
     */
    public function index(Request $request): View
    {
        $favorites = Product::with(['primaryImage', 'user'])
            ->whereHas('favorites', fn ($q) => $q->where('user_id', $request->user()->id))
            ->latest()
            ->get();

        return view('favorites.index', [
            'favorites' => $favorites,
            'count' => $favorites->count(),
        ]);
    }

    /**
     * Toggle status favorit suatu produk untuk user yang login (tabel favorites).
     */
    public function toggle(Request $request, int|string $id): JsonResponse|RedirectResponse
    {
        $userId = $request->user()->id;

        if (! Product::whereKey($id)->exists()) {
            abort(404, 'Produk tidak ditemukan');
        }

        $existing = Favorite::where('user_id', $userId)->where('product_id', $id)->first();

        if ($existing) {
            $existing->delete();
            $isFavorite = false;
            $message = 'Produk dihapus dari favorit.';
        } else {
            Favorite::create(['user_id' => $userId, 'product_id' => (int) $id]);
            $isFavorite = true;
            $message = 'Produk berhasil ditambahkan ke favorit.';
        }

        $count = Product::whereHas('favorites', fn ($q) => $q->where('user_id', $userId))->count();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'is_favorite' => $isFavorite,
                'product_id' => (int) $id,
                'count' => $count,
                'message' => $message,
            ]);
        }

        return back()->with('status', $message);
    }
}
