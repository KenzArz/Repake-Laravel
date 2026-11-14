<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Illuminate\Http\UploadedFile;

class ProfileController extends Controller
{
    /**
     * Tab di profil pemilik (termasuk daftar favorit pribadi).
     *
     * @var array<int, string>
     */
    private const OWNER_TABS = ['dijual', 'favorit', 'ulasan'];

    /**
     * Tab di profil publik penjual lain (tanpa favorit).
     *
     * @var array<int, string>
     */
    private const PUBLIC_TABS = ['dijual', 'ulasan'];

    /**
     * Tampilkan halaman profil dan katalog produk pengguna.
     */
    public function catalog(Request $request): View
    {
        $tab = $this->resolveTab($request, self::OWNER_TABS);
        $status = $this->resolveStatus($request);
        $sort = $this->resolveSort($request);

        return view('profile.catalog', $this->catalogPayload($request->user(), $tab, true, $status, $sort));
    }

    /**
     * Tampilkan profil publik penjual lain.
     *
     * Hanya produk berstatus "dijual" atau "terjual" yang tampil, sehingga produk
     * yang ditangguhkan admin atau masih draft tidak bocor lewat halaman ini.
     * Penjual yang dibanned tidak bisa diakses, sama seperti detail produknya.
     */
    public function show(Request $request, string $seller): View|RedirectResponse
    {
        $user = $this->findSeller($request, $seller);

        abort_if($user->is_banned, 404);

        if ($request->user()?->id === $user->id) {
            return redirect()->route('profile.catalog');
        }

        // Kanonis: username bila ada, selain itu UUID. `?id={uuid}` selalu
        // disertakan untuk akun ber-username agar identitas tetap stabil
        // meski username berubah.
        $canonicalKey = $user->username ?: $user->id;
        $needsQueryId = filled($user->username);

        if ($seller !== $canonicalKey || ($needsQueryId && ! $request->filled('id'))) {
            return redirect()->to($user->profileUrl(), 301);
        }

        $tab = $this->resolveTab($request, self::PUBLIC_TABS);
        $status = $this->resolveStatus($request);
        $sort = $this->resolveSort($request);

        return view('profile.catalog', $this->catalogPayload($user, $tab, false, $status, $sort));
    }

    /**
     * Tampilkan formulir edit profil.
     */
    public function edit(): View
    {
        return view('profile.edit', [
            'profile' => $this->profileData(Auth::user()),
        ]);
    }

    /**
     * Simpan perubahan profil pengguna.
     */
    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = Auth::user();

        // Perbarui data utama profil
        $user->name = trim($validated['name']);

        // Username kosong berarti akun memakai UUID pada URL profil,
        // jadi pengguna tetap boleh menghapus username-nya.
        $user->username = filled($validated['username'] ?? null)
            ? ltrim(trim((string) $validated['username']), '@')
            : null;

        $user->phone = ! empty($validated['phone']) ? trim($validated['phone']) : null;
        $user->kota = ! empty($validated['kota']) ? trim($validated['kota']) : 'Karawang';
        $user->bio = ! empty($validated['bio']) ? trim($validated['bio']) : null;

        // Upload foto profil jika pengguna memilih gambar baru (otomatis convert ke .webp)
        if ($request->hasFile('avatar')) {
            $this->deleteStoredAvatar($user->avatar);
            $user->avatar = $this->storeAvatarAsWebp($request->file('avatar'));
        }

        // Hapus foto jika diminta
        if ($request->filled('remove_avatar') && $request->input('remove_avatar') == '1') {
            $this->deleteStoredAvatar($user->avatar);
            $user->avatar = null;
        }

        /** @var User $user */
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
     * Konversi dan simpan foto avatar ke format WebP pada disk publik.
     */
    private function storeAvatarAsWebp(UploadedFile $file): string
    {
        if (! function_exists('imagecreatefromstring') || ! function_exists('imagewebp')) {
            return $file->store('avatars', 'public');
        }

        $raw = @file_get_contents($file->getRealPath());
        $image = $raw !== false ? @imagecreatefromstring($raw) : false;

        if ($image === false) {
            return $file->store('avatars', 'public');
        }

        // Perbaiki rotasi otomatis foto kamera HP (EXIF Orientation) jika JPEG
        if (function_exists('exif_read_data') && $file->getMimeType() === 'image/jpeg') {
            $exif = @exif_read_data($file->getRealPath());
            $orientation = (int) ($exif['Orientation'] ?? 1);

            $image = match ($orientation) {
                3 => imagerotate($image, 180, 0) ?: $image,
                6 => imagerotate($image, -90, 0) ?: $image,
                8 => imagerotate($image, 90, 0) ?: $image,
                default => $image,
            };
        }

        // Pertahankan transparansi (alpha channel) untuk PNG/WebP
        if (function_exists('imagepalettetotruecolor')) {
            imagepalettetotruecolor($image);
        }
        imagealphablending($image, true);
        imagesavealpha($image, true);

        ob_start();
        $encoded = imagewebp($image, null, 82);
        $webpContent = ob_get_clean();
        unset($image);

        if (! $encoded || $webpContent === false || $webpContent === '') {
            return $file->store('avatars', 'public');
        }

        $path = 'avatars/' . Str::uuid() . '.webp';
        Storage::disk('public')->put($path, $webpContent);

        return $path;
    }

    /**
     * Hapus berkas avatar lama dari disk publik tanpa menyentuh URL eksternal
     * (mis. foto Google) atau aset bawaan yang tersimpan di public/.
     */
    private function deleteStoredAvatar(?string $path): void
    {
        if (
            ! $path
            || str_starts_with($path, 'http://')
            || str_starts_with($path, 'https://')
            || str_starts_with($path, '/')
        ) {
            return;
        }

        if (file_exists(public_path($path))) {
            return;
        }

        Storage::disk('public')->delete($path);
    }

    /**
     * Data siap-render yang dipakai bersama oleh profil sendiri dan profil publik.
     *
     * @return array<string, mixed>
     */
    private function catalogPayload(
        User $user,
        string $tab,
        bool $isOwner,
        string $status = 'semua',
        string $sort = 'terbaru'
    ): array {
        $products = $this->productsFor($user, $tab, $isOwner, $status, $sort);
        $reviews = $this->reviewsFor($user);

        // Statistik profile dihitung dari data nyata, bukan hardcode.
        $profile = $this->profileData($user);
        $profile['rating'] = round((float) ($reviews->avg(fn(array $review): int => $review['rating']) ?? 0), 1);
        $profile['reviews_count'] = $reviews->count();
        $profile['sold_count'] = $this->soldCount($user);

        // Ulasan penjual->pembeli: pengulas melihat form di profil pembeli.
        $reviewableOrder = $this->reviewableOrderFor($user, $tab, $isOwner);

        return [
            'profile' => $profile,
            'seller' => $user,
            'isOwner' => $isOwner,
            'tab' => $tab,
            'status' => $status,
            'sort' => $sort,
            'products' => $products,
            'reviews' => $reviews->all(),
            'reviewCount' => $reviews->count(),
            'reviewableOrder' => $reviewableOrder,
            'favoriteCount' => $isOwner ? $this->favoriteCount($user) : 0,
            'chatProducts' => $isOwner ? collect() : $this->activeProducts($user),
            'profileUrl' => $isOwner ? route('profile.catalog') : $user->profileUrl(),
        ];
    }

    /**
     * Order yang memungkinkan pengunjung (sebagai penjual) mengulas pemilik
     * profil (sebagai pembeli). Hanya relevan pada tab ulasan profil orang lain.
     */
    private function reviewableOrderFor(User $user, string $tab, bool $isOwner): ?Order
    {
        if ($isOwner || $tab !== 'ulasan' || ! Auth::check()) {
            return null;
        }

        $viewerId = (string) Auth::id();

        $order = Order::query()
            ->where('seller_id', $viewerId)
            ->where('buyer_id', $user->id)
            ->whereIn('status', [Order::STATUS_DISEPAKATI, Order::STATUS_SELESAI])
            ->latest('id')
            ->first();

        if ($order === null || $order->reviews()->where('reviewer_id', $viewerId)->exists()) {
            return null;
        }

        return $order;
    }

    /**
     * Produk sesuai tab, filter status, dan urutan yang sedang aktif.
     *
     * @return Collection<int, Product>
     */
    private function productsFor(
        User $user,
        string $tab,
        bool $isOwner,
        string $status = 'semua',
        string $sort = 'terbaru'
    ): Collection {
        if ($tab === 'ulasan') {
            return collect();
        }

        if ($isOwner && $tab === 'favorit') {
            // Samakan dengan katalog: produk yang disuspend/draft atau milik
            // penjual yang dibanned tidak ditampilkan agar tidak menuju 404.
            $query = Product::with(['primaryImage', 'user'])
                ->dijual()
                ->whereHas('favorites', fn($query) => $query->where('user_id', $user->id));

            return $this->applySort($query, $sort)->get();
        }

        $query = Product::with(['primaryImage', 'user'])
            ->where('user_id', $user->id);

        if ($status === 'dijual') {
            $query->where('status', Product::STATUS_DIJUAL);
        } elseif ($status === 'terjual') {
            $query->where('status', Product::STATUS_TERJUAL);
        } elseif (! $isOwner) {
            $query->whereIn('status', [Product::STATUS_DIJUAL, Product::STATUS_TERJUAL]);
        }

        return $this->applySort($query, $sort)->get();
    }

    /**
     * Terapkan urutan produk: terbaru, terlama, atau paling diminati (jumlah favorit).
     */
    private function applySort($query, string $sort)
    {
        return match ($sort) {
            'terlama' => $query->oldest(),
            'diminati' => $query->withCount('favorites')->orderByDesc('favorites_count')->latest(),
            default => $query->latest(),
        };
    }

    /**
     * Jumlah produk berstatus terjual untuk angka "Terjual" pada kartu profil.
     */
    private function soldCount(User $user): int
    {
        return Product::query()
            ->where('user_id', $user->id)
            ->where('status', Product::STATUS_TERJUAL)
            ->count();
    }

    /**
     * Jumlah produk untuk angka "Terjual" pada kartu profil.
     */
    private function productCount(User $user, bool $isOwner): int
    {
        return Product::query()
            ->where('user_id', $user->id)
            ->when(! $isOwner, fn($query) => $query->where('status', Product::STATUS_DIJUAL))
            ->count();
    }

    /**
     * Jumlah produk yang difavoritkan pemilik profil (tab Favorit).
     */
    private function favoriteCount(User $user): int
    {
        return Product::query()
            ->dijual()
            ->whereHas('favorites', fn($query) => $query->where('user_id', $user->id))
            ->count();
    }

    /**
     * Produk aktif penjual untuk modal "Chat Penjual" di profil publik.
     *
     * @return Collection<int, Product>
     */
    private function activeProducts(User $user): Collection
    {
        return Product::with('primaryImage')
            ->where('user_id', $user->id)
            ->where('status', Product::STATUS_DIJUAL)
            ->latest()
            ->get();
    }

    /**
     * Ulasan dari pembeli untuk produk pemilik profil (dari database).
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function reviewsFor(User $user): Collection
    {
        return Review::with(['product.primaryImage', 'reviewer'])
            ->where('reviewee_id', $user->id)
            ->latest()
            ->get()
            ->map(fn(Review $review): array => [
                'id' => $review->id,
                'reviewer_id' => $review->reviewer_id,
                'is_mine' => (string) $review->reviewer_id === (string) Auth::id(),
                'reviewer_name' => $review->reviewer?->name ?? 'Pengguna',
                'time_ago' => $review->created_at?->locale('id')->diffForHumans(['syntax' => CarbonInterface::DIFF_ABSOLUTE]) . ' lalu',
                'rating' => $review->rating,
                'comment' => $review->komentar ?? '',
                'has_product' => $review->product !== null,
                'product_title' => $review->product?->judul ?? '',
                'product_price' => 'Rp ' . number_format($review->product?->harga ?? 0, 0, ',', '.'),
                'product_image' => $review->product?->gambar ?? 'images/default-avatar.png',
            ]);
    }

    /**
     * Data profil dari user yang diberikan (bukan hanya user yang login).
     *
     * @return array<string, mixed>
     */
    private function profileData(User $user): array
    {
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

    /**
     * Cari penjual dari query `id` (UUID) atau dari segmen path (username/UUID).
     *
     * Fallback nomor urut sudah dihapus agar akun tidak bisa dienumerasi.
     */
    private function findSeller(Request $request, string $seller): User
    {
        $id = $request->query('id');

        if (filled($id)) {
            abort_unless(is_string($id) && Str::isUuid($id), 404);

            $user = User::query()->find($id);
        } else {
            $user = User::query()->where('username', $seller)->first()
                ?? (Str::isUuid($seller) ? User::query()->find($seller) : null);
        }

        abort_if($user === null, 404);

        return $user;
    }

    /**
     * Tab yang dipakai, dibatasi ke daftar tab yang tersedia.
     *
     * @param  array<int, string>  $allowed
     */
    private function resolveTab(Request $request, array $allowed): string
    {
        $tab = (string) $request->query('tab', 'dijual');

        return in_array($tab, $allowed, true) ? $tab : 'dijual';
    }

    /**
     * Filter status produk pada tab Produk.
     */
    private function resolveStatus(Request $request): string
    {
        $status = (string) $request->query('status', 'semua');

        return in_array($status, ['semua', 'dijual', 'terjual'], true) ? $status : 'semua';
    }

    /**
     * Urutan produk yang dipilih pengguna.
     */
    private function resolveSort(Request $request): string
    {
        $sort = (string) $request->query('sort', 'terbaru');

        return in_array($sort, ['terbaru', 'terlama', 'diminati'], true) ? $sort : 'terbaru';
    }
}
