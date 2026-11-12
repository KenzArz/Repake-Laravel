<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    /**
     * Tampilkan halaman daftar produk favorit dari database.
     */
    public function index(Request $request): View
    {
        $favorites = Product::with(['primaryImage', 'user'])
            ->dijual()
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
     *
     * Parameter rute adalah UUID produk, bukan slug atau nomor urut.
     */
    public function toggle(Request $request, string $id): JsonResponse|RedirectResponse
    {
        abort_unless(Str::isUuid($id), 404);

        $product = Product::query()->find($id);

        if (! $product) {
            abort(404, 'Produk tidak ditemukan');
        }

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

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'is_favorite' => $isFavorite,
                'product_id' => $productId,
                'count' => $count,
                'message' => $message,
            ]);
        }

        return back()->with('status', $message);
    }
}
