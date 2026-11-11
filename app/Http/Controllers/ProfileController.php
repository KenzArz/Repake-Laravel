<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Tampilkan halaman profil dan katalog produk pengguna.
     */
    public function catalog(Request $request): View
    {
        $user = $request->user();
        $profile = $this->getProfile();
        $tab = $request->query('tab', 'dijual');

        // Produk yang dijual oleh pengguna (dari database)
        $soldProducts = Product::with(['primaryImage', 'user'])
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        // Produk yang difavoritkan oleh pengguna (dari tabel favorites)
        $favoriteProducts = Product::with(['primaryImage', 'user'])
            ->whereHas('favorites', fn ($q) => $q->where('user_id', $user->id))
            ->latest()
            ->get();

        // Tentukan produk sesuai tab aktif
        $products = $tab === 'favorit' ? $favoriteProducts : $soldProducts;

        // Ulasan pembeli tentang barang yang sudah terjual (dari database)
        $reviews = $this->getReviews($user);

        // Statistik profile dihitung dari data nyata, bukan hardcode
        $profile['rating'] = round((float) ($reviews->avg(fn (array $r): int => $r['rating']) ?? 0), 1);
        $profile['reviews_count'] = $reviews->count();
        $profile['sold_count'] = $soldProducts->count();

        return view('profile.catalog', [
            'profile' => $profile,
            'tab' => $tab,
            'products' => $products,
            'reviews' => $reviews->all(),
            'soldCount' => $soldProducts->count(),
            'favoriteCount' => $favoriteProducts->count(),
            'reviewCount' => $reviews->count(),
        ]);
    }

    /**
     * Ulasan dari pembeli untuk produk pemilik profile (dari database).
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function getReviews(User $user): Collection
    {
        return Review::with(['product.primaryImage', 'reviewer'])
            ->where('seller_id', $user->id)
            ->latest()
            ->get()
            ->map(fn (Review $review): array => [
                'id' => $review->id,
                'reviewer_name' => $review->reviewer?->name ?? 'Pembeli',
                'time_ago' => $review->created_at?->locale('id')->diffForHumans(['syntax' => CarbonInterface::DIFF_ABSOLUTE]).' lalu',
                'rating' => $review->rating,
                'comment' => $review->komentar ?? '',
                'product_title' => $review->product?->judul ?? '',
                'product_price' => 'Rp '.number_format($review->product?->harga ?? 0, 0, ',', '.'),
                'product_image' => $review->product?->gambar ?? 'images/default-avatar.png',
            ]);
    }

    /**
     * Tampilkan formulir edit profil.
     */
    public function edit(): View
    {
        $profile = $this->getProfile();

        return view('profile.edit', [
            'profile' => $profile,
        ]);
    }

    /**
     * Simpan perubahan profil pengguna.
     */
    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = auth()->user();

        // Perbarui data utama profil
        $user->name = trim($validated['name']);
        if (! empty($validated['username'])) {
            $user->username = ltrim(trim($validated['username']), '@');
        }
        $user->phone = ! empty($validated['phone']) ? trim($validated['phone']) : null;
        $user->kota = ! empty($validated['kota']) ? trim($validated['kota']) : 'Karawang';
        $user->bio = ! empty($validated['bio']) ? trim($validated['bio']) : null;

        // Upload foto profil jika pengguna memilih gambar baru
        if ($request->hasFile('avatar')) {
            $user->avatar = $request->file('avatar')->store('avatars', 'public');
        }

        // Hapus foto jika diminta
        if ($request->filled('remove_avatar') && $request->input('remove_avatar') == '1') {
            $user->avatar = null;
        }

        $user->save();

        session()->put('user_profile', [
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
            'kota' => $user->kota ?: 'Karawang',
            'avatar' => $user->avatar_url,
            'is_verified' => true,
        ]);

        return redirect()->route('profile.catalog')->with('success', 'Profil berhasil diperbarui!');
    }

    /**
     * Ambil data profil dari user yang sedang login.
     *
     * @return array<string, mixed>
     */
    private function getProfile(): array
    {
        $user = auth()->user();

        return [
            'name' => $user->name,
            'username' => $user->username ?: str($user->email)->before('@')->replace('.', '_')->toString(),
            'email' => $user->email,
            'phone' => $user->phone,
            'kota' => $user->kota ?: 'Karawang',
            'bio' => $user->bio,
            'avatar' => $user->avatar,
            'avatar_url' => $user->avatar_url,
        ];
    }
}
