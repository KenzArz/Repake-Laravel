<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Tampilkan halaman profil dan katalog produk pengguna.
     */
    public function catalog(Request $request): View
    {
        $user = Auth::user();
        $profile = $this->getProfile();
        $tab = $request->query('tab', 'dijual');

        // 1. Produk yang dijual oleh pengguna (dari Database)
        $soldProducts = $user
            ? Product::with(['user', 'primaryImage', 'images', 'category'])
            ->where('user_id', $user->id)
            ->latest()
            ->get()
            : collect();

        // 2. Produk yang difavoritkan oleh pengguna (dari Database)
        $favoriteProducts = $user
            ? Product::with(['user', 'primaryImage', 'images', 'category'])
            ->whereHas('favorites', fn($q) => $q->where('user_id', $user->id))
            ->latest()
            ->get()
            : collect();

        // Data ulasan dari pembeli
        $reviews = $this->getDummyReviews();

        // Tentukan produk sesuai tab aktif
        $products = match ($tab) {
            'favorit' => $favoriteProducts,
            default => $soldProducts,
        };

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
     * Data ulasan pembeli tentang barang yang sudah terjual.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getDummyReviews(): array
    {
        return [
            [
                'id' => 1,
                'reviewer_name' => 'Sarah Amalia',
                'rating' => 5,
                'time_ago' => '3 hari lalu',
                'product_title' => 'TWS Earbuds Bluetooth Wireless Second',
                'product_price' => 'Rp 150.000',
                'product_image' => 'https://images.unsplash.com/photo-1590658268037-6bf12165a8df?w=200&auto=format&fit=crop&q=80',
                'comment' => 'Penjual ramah banget! COD di kampus unsika langsung dites barangnya dan masih berfungsi normal 100%. Recommended seller pokoknya!',
            ],
            [
                'id' => 2,
                'reviewer_name' => 'Budi Pratama',
                'rating' => 5,
                'time_ago' => '1 minggu lalu',
                'product_title' => 'Buku Pemrograman Web Dasar Laravel',
                'product_price' => 'Rp 75.000',
                'product_image' => 'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?w=200&auto=format&fit=crop&q=80',
                'comment' => 'Barang sesuai deskripsi, masih sangat mulus tanpa coretan. Harganya jauh lebih murah dari toko buku. Makasih mas!',
            ],
            [
                'id' => 3,
                'reviewer_name' => 'Rian Kurniawan',
                'rating' => 4,
                'time_ago' => '2 minggu lalu',
                'product_title' => 'Mouse Gaming Logitech G102 Bekas',
                'product_price' => 'Rp 120.000',
                'product_image' => 'https://images.unsplash.com/photo-1615663245857-ac93bb7c39e7?w=200&auto=format&fit=crop&q=80',
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
        $user = Auth::user();
        $validated = $request->validated();

        $user->name = trim($validated['name']);
        $user->username = trim($validated['username']);
        $user->whatsapp_number = ! empty($validated['phone']) ? trim($validated['phone']) : null;
        $user->city = ! empty($validated['kota']) ? trim($validated['kota']) : 'Karawang';
        $user->bio = ! empty($validated['bio']) ? trim($validated['bio']) : null;

        // Upload foto profil jika pengguna memilih gambar baru
        if ($request->hasFile('avatar')) {
            $avatarFile = $request->file('avatar');
            $filename = 'avatar_' . time() . '_' . uniqid() . '.' . $avatarFile->getClientOriginalExtension();
            $destinationPath = public_path('images/avatars');

            if (! file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            $avatarFile->move($destinationPath, $filename);
            $user->avatar = 'images/avatars/' . $filename;
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
        $user = Auth::user();

        return [
            'name' => $user?->name ?? 'Mahasiswa UNSIKA',
            'username' => $user?->username ?: str($user?->email ?? 'user')->before('@')->replace('.', '_')->toString(),
            'email' => $user?->email ?? '',
            'phone' => $user?->whatsapp_number ?? '',
            'kota' => $user?->city ?: 'Karawang',
            'bio' => $user?->bio ?? 'Belum ada bio.',
            'avatar' => $user?->avatar,
            'rating' => 4.8,
            'reviews_count' => 3,
            'sold_count' => Product::where('user_id', $user?->id)->count(),
            'online_status' => 'Aktif sekarang',
            'is_verified' => true,
        ];
    }
}
