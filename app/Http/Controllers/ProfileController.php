<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Tampilkan halaman profil dan katalog produk pengguna.
     */
    public function catalog(Request $request): View
    {
        $profile = $this->getProfile();
        $tab = $request->query('tab', 'dijual');
        $user = auth()->user();

        // 1. Produk yang dijual oleh pengguna
        $dbSold = $user ? Product::with(['primaryImage', 'images', 'codAreas'])->where('user_id', $user->id)->get() : collect();

        if ($dbSold->isNotEmpty()) {
            $soldProducts = $dbSold;
        } else {
            // Fallback dummy
            $allProducts = Product::dummyCatalog();
            $soldProducts = $allProducts->take(8)->map(function (object $product) use ($profile): object {
                $clone = clone $product;
                $clone->penjual = $profile['name'] ?? $profile['username'] ?? 'Midou Wijaya';
                $clone->kota = $profile['kota'] ?? 'Bekasi';

                return $clone;
            });
        }

        // 2. Produk yang difavoritkan oleh pengguna
        $dbFavorites = $user
            ? Product::with(['user', 'primaryImage', 'images', 'codAreas'])->whereHas('favorites', fn ($q) => $q->where('user_id', $user->id))->get()
            : collect();

        if ($dbFavorites->isNotEmpty()) {
            $favoriteProducts = $dbFavorites;
        } else {
            $favoriteIds = session()->get('favorites', []);
            $favoriteProducts = Product::dummyCatalog()
                ->filter(fn (object $p): bool => in_array($p->id, $favoriteIds, true))
                ->values();
        }

        // Tentukan produk sesuai tab aktif
        $products = $tab === 'favorit' ? $favoriteProducts : $soldProducts;

        // Data ulasan dari pembeli
        $reviews = $this->getDummyReviews();

        return view('profile.catalog', [
            'profile' => $profile,
            'tab' => $tab,
            'products' => $products,
            'reviews' => $reviews,
            'soldCount' => $soldProducts->count(),
            'favoriteCount' => $favoriteProducts->count(),
            'reviewCount' => count($reviews),
        ]);
    }

    /**
     * Data ulasan dummy pembeli tentang barang yang sudah terjual.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getDummyReviews(): array
    {
        return [
            [
                'id' => 1,
                'author' => 'Sarah Amalia',
                'author_avatar' => null,
                'rating' => 5,
                'date' => '3 hari lalu',
                'product_title' => 'TWS Earbuds Bluetooth Wireless Second',
                'comment' => 'Penjual ramah banget! COD di kampus unsika langsung dites barangnya dan masih berfungsi normal 100%. Recommended seller pokoknya!',
            ],
            [
                'id' => 2,
                'author' => 'Budi Pratama',
                'author_avatar' => null,
                'rating' => 5,
                'date' => '1 minggu lalu',
                'product_title' => 'Buku Pemrograman Web Dasar Laravel',
                'comment' => 'Barang sesuai deskripsi, masih sangat mulus tanpa coretan. Harganya jauh lebih murah dari toko buku. Makasih mas!',
            ],
            [
                'id' => 3,
                'author' => 'Rian Kurniawan',
                'author_avatar' => null,
                'rating' => 4,
                'date' => '2 minggu lalu',
                'product_title' => 'Mouse Gaming Logitech G102 Bekas',
                'comment' => 'Pengiriman cepat, packing aman. Cuma ada baret halus pemakaian wajar tapi klik masih enak dan responsif.',
            ],
        ];
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
     * Simpan pembaruan profil pengguna.
     */
    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = auth()->user();
        $validated = $request->validated();

        $user->name = trim($validated['name']);
        $user->username = trim($validated['username']);
        $user->whatsapp_number = ! empty($validated['phone']) ? trim($validated['phone']) : null;
        $user->city = ! empty($validated['kota']) ? trim($validated['kota']) : 'Karawang';
        $user->bio = ! empty($validated['bio']) ? trim($validated['bio']) : null;

        // Upload foto profil jika pengguna memilih gambar baru
        if ($request->hasFile('avatar')) {
            $avatarFile = $request->file('avatar');
            $filename = 'avatar_'.time().'_'.uniqid().'.'.$avatarFile->getClientOriginalExtension();
            $destinationPath = public_path('images/avatars');

            if (! file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            $avatarFile->move($destinationPath, $filename);
            $user->avatar = 'images/avatars/'.$filename;
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
            'kota' => $user->city ?: 'Karawang',
            'avatar' => $user->avatar,
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
            'name' => $user?->name ?? 'Midou Wijaya',
            'username' => $user?->username ?: str($user?->email ?? 'midou@student.unsika.ac.id')->before('@')->replace('.', '_')->toString(),
            'email' => $user?->email ?? 'midou@student.unsika.ac.id',
            'phone' => $user?->whatsapp_number ?? '081299887766',
            'kota' => $user?->city ?: 'Bekasi',
            'bio' => $user?->bio ?? 'Jual apa aja demi bisa nikahin hiura',
            'avatar' => $user?->avatar,
            'rating' => 4.5,
            'reviews_count' => 67,
            'sold_count' => 10,
            'online_status' => 'Online 10 menit lalu',
            'is_verified' => true,
        ];
    }
}
