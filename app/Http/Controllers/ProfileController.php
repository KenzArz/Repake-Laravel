<?php

namespace App\Http\Controllers;

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
        $allProducts = Product::dummyCatalog();

        // Produk yang dijual oleh pengguna
        $soldProducts = $allProducts->take(5)->map(function (object $product) use ($profile): object {
            $clone = clone $product;
            $clone->penjual = $profile['name'];
            $clone->kota = $profile['kota'];

            return $clone;
        });

        // Produk yang difavoritkan oleh pengguna
        $favoriteIds = session()->get('favorites', []);
        $favoriteProducts = $allProducts
            ->filter(fn (object $p): bool => in_array($p->id, $favoriteIds, true))
            ->values();

        // Tentukan produk sesuai tab aktif
        $products = $tab === 'favorit' ? $favoriteProducts : $soldProducts;

        return view('profile.catalog', [
            'profile' => $profile,
            'tab' => $tab,
            'products' => $products,
            'soldCount' => $soldProducts->count(),
            'favoriteCount' => $favoriteProducts->count(),
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
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'nullable|string|max:30',
            'kota' => 'nullable|string|max:100',
            'bio' => 'nullable|string|max:500',
            'avatar' => 'nullable|image|max:2048',
        ]);

        $profile = $this->getProfile();
        $profile['name'] = $validated['name'];
        $profile['phone'] = $validated['phone'] ?? $profile['phone'];
        $profile['kota'] = $validated['kota'] ?? $profile['kota'];
        $profile['bio'] = $validated['bio'] ?? $profile['bio'];

        // Upload foto profil jika ada
        if ($request->hasFile('avatar')) {
            $avatarFile = $request->file('avatar');
            $filename = 'avatar_'.time().'.'.$avatarFile->getClientOriginalExtension();
            $destinationPath = public_path('images/avatars');

            if (! file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            $avatarFile->move($destinationPath, $filename);
            $profile['avatar'] = 'images/avatars/'.$filename;
        }

        session()->put('user_profile', $profile);

        return redirect()->route('profile.catalog')->with('success', 'Profil berhasil diperbarui!');
    }

    /**
     * Logout pengguna.
     */
    public function logout(Request $request): RedirectResponse
    {
        if (auth()->check()) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()->route('home')->with('status', 'Anda telah keluar dari akun.');
    }

    /**
     * Ambil data profil dari session atau nilai default.
     *
     * @return array<string, mixed>
     */
    private function getProfile(): array
    {
        if (! session()->has('user_profile')) {
            session()->put('user_profile', [
                'name' => 'Midou_kun',
                'email' => 'midou.kun@student.unsika.ac.id',
                'phone' => '0812-3456-7890',
                'kota' => 'Bekasi',
                'bio' => 'Sedia perifer PC & setup mechanical keyboard bekas berkualitas.',
                'avatar' => null,
                'rating' => 4.5,
                'reviews_count' => 48,
                'online_status' => 'Online 10 menit lalu',
                'is_verified' => true,
            ]);
        }

        return session()->get('user_profile');
    }
}
