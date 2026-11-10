<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class ProfileController extends Controller
{
    /**
     * Tampilkan halaman profil dan katalog produk pengguna.
     */
    public function catalog(Request $request): View
    {
        $profile = $this->getProfile();
        $tab = $request->query('tab', 'dijual');
        $allProducts = Product::dummyCatalog();

        // Produk yang dijual oleh pengguna (8 produk seperti di wireframe)
        $soldProducts = $allProducts->take(8)->map(function (object $product) use ($profile): object {
            $clone = clone $product;
            $clone->penjual = $profile['name'] ?? $profile['username'] ?? 'Midou Wijaya';
            $clone->kota = $profile['kota'] ?? 'Bekasi';

            return $clone;
        });

        // Produk yang difavoritkan oleh pengguna
        $favoriteIds = session()->get('favorites', []);
        $favoriteProducts = $allProducts
            ->filter(fn (object $p): bool => in_array($p->id, $favoriteIds, true))
            ->values();

        // Tentukan produk sesuai tab aktif
        $products = $tab === 'favorit' ? $favoriteProducts : $soldProducts;

        // Data ulasan dari pembeli tentang barang yang sudah terjual
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
     * Data ulasan dummy pembeli tentang barang yang sudah terjual (sesuai wireframe).
     *
     * @return array<int, array<string, mixed>>
     */
    private function getDummyReviews(): array
    {
        return [
            [
                'id' => 1,
                'reviewer_name' => 'Aleww_kun',
                'time_ago' => '2 jam lalu',
                'rating' => 5,
                'comment' => 'Penjual fast respon, bass nya mendut mendut, puas dipake seharian',
                'product_title' => 'Figur anime lucu',
                'product_price' => 'Rp 100.000',
                'product_image' => 'images/products/samsung-buds-review.png',
            ],
            [
                'id' => 2,
                'reviewer_name' => 'Aleww_kun',
                'time_ago' => '2 jam lalu',
                'rating' => 5,
                'comment' => 'Penjual fast respon, bass nya mendut mendut, puas dipake seharian',
                'product_title' => 'Samsung galaxy ear buds',
                'product_price' => 'Rp 100.000',
                'product_image' => 'images/products/samsung-buds-review.png',
            ],
            [
                'id' => 3,
                'reviewer_name' => 'Aleww_kun',
                'time_ago' => '2 jam lalu',
                'rating' => 5,
                'comment' => 'Penjual fast respon, bass nya mendut mendut, puas dipake seharian',
                'product_title' => 'Samsung galaxy ear buds',
                'product_price' => 'Rp 100.000',
                'product_image' => 'images/products/samsung-buds-review.png',
            ],
            [
                'id' => 4,
                'reviewer_name' => 'Rian_FT_Unsika',
                'time_ago' => '1 hari lalu',
                'rating' => 5,
                'comment' => 'Keyboard mechanical nya empuk dan mulus banget. COD di depan GSG Unsika aman & ramah!',
                'product_title' => 'Keyboard pemda bekas reza auditore asli no hoax',
                'product_price' => 'Rp 670.000',
                'product_image' => 'https://images.unsplash.com/photo-1595225476474-87563907a212?w=200&auto=format&fit=crop&q=80',
            ],
            [
                'id' => 5,
                'reviewer_name' => 'Siti_Nurhaliza',
                'time_ago' => '3 hari lalu',
                'rating' => 5,
                'comment' => 'Kemeja flanelnya bersih dan wangi, pas banget ukurannya. Recommended seller buat anak kampus.',
                'product_title' => 'Kemeja pria warna merah motif kotak lengan panjang',
                'product_price' => 'Rp 170.000',
                'product_image' => 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=200&auto=format&fit=crop&q=80',
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
     * Simpan perubahan profil pengguna.
     */
    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $profile = $this->getProfile();

        // Perbarui data utama profil
        $profile['name'] = trim($validated['name']);
        if (! empty($validated['username'])) {
            $profile['username'] = ltrim(trim($validated['username']), '@');
        }
        $profile['phone'] = ! empty($validated['phone']) ? trim($validated['phone']) : null;
        $profile['kota'] = ! empty($validated['kota']) ? trim($validated['kota']) : 'Karawang';
        $profile['bio'] = ! empty($validated['bio']) ? trim($validated['bio']) : null;

        // Upload foto profil jika pengguna memilih gambar baru
        if ($request->hasFile('avatar')) {
            $avatarFile = $request->file('avatar');
            $filename = 'avatar_'.time().'_'.uniqid().'.'.$avatarFile->getClientOriginalExtension();
            $destinationPath = public_path('images/avatars');

            if (! file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            $avatarFile->move($destinationPath, $filename);
            $profile['avatar'] = 'images/avatars/'.$filename;
        }

        // Hapus foto jika diminta
        if ($request->filled('remove_avatar') && $request->input('remove_avatar') == '1') {
            $profile['avatar'] = null;
        }

        session()->put('user_profile', $profile);

        // Sinkronkan nama dengan user yang sedang terautentikasi (jika ada)
        if (auth()->check()) {
            $user = auth()->user();
            $user->name = $profile['name'];
            try {
                $user->save();
            } catch (Throwable) {
                // Abaikan jika database in-memory / read-only
            }
        }

        return redirect()->route('profile.catalog')->with('success', 'Profil berhasil diperbarui!');
    }

    /**
     * Logout pengguna.
     */
    public function logout(Request $request): RedirectResponse
    {
        if (auth()->check()) {
            auth()->logout();
            $request->session()->forget(['auth_user', 'user_profile']);
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()->route('home')->with('status', 'Anda telah keluar dari akun.');
    }

    /**
     * Ambil data profil dari session atau nilai default terverifikasi.
     *
     * @return array<string, mixed>
     */
    private function getProfile(): array
    {
        $defaults = [
            'name' => 'Midou Wijaya',
            'username' => 'Midou_kun',
            'email' => 'midou.kun@student.unsika.ac.id',
            'phone' => '0812-3456-7890',
            'kota' => 'Bekasi',
            'bio' => 'Jual apa aja demi bisa nikahin hiura',
            'avatar' => null,
            'rating' => 4.5,
            'reviews_count' => 67,
            'sold_count' => 10,
            'online_status' => 'Online 10 menit lalu',
            'is_verified' => true,
        ];

        $current = session()->get('user_profile', []);
        $merged = array_merge($defaults, $current);

        if (auth()->check()) {
            if (! empty(auth()->user()->name) && empty($current['name'])) {
                $merged['name'] = auth()->user()->name;
            }
            if (! empty(auth()->user()->email)) {
                $merged['email'] = auth()->user()->email;
            }
        }

        session()->put('user_profile', $merged);

        return $merged;
    }
}
