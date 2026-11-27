<?php

namespace App\Http\Controllers\Demo;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class DemoLoginController extends Controller
{
    /**
     * Masuk sebagai persona demo tanpa akun kampus.
     *
     * Setiap perpindahan persona selalu memulai sesi baru, bukan menumpuk
     * sesi lama, agar satu sesi tidak pernah mewakili dua pihak sekaligus.
     */
    public function login(Request $request, string $persona): RedirectResponse
    {
        abort_unless(config('demo.enabled'), 404);

        $personas = config('demo.personas');

        abort_unless(is_array($personas) && array_key_exists($persona, $personas), 404);

        $user = User::query()->where('email', $personas[$persona])->firstOrFail();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Auth::login($user, true);
        $request->session()->regenerate();

        // Navbar, form produk, dan halaman profil membaca profil dari session.
        $request->session()->put('user_profile', [
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
            'kota' => $user->kota ?: 'Karawang',
            'avatar' => $user->avatar_url,
            'is_verified' => true,
        ]);

        return redirect($this->destination($request, $persona));
    }

    /**
     * Halaman tujuan setelah login.
     *
     * Persona admin hanya boleh mendarat di wilayah admin; pembeli dan
     * penjual hanya di halaman yang memang bisa mereka buka. Tujuan lain
     * (mis. /admin/dashboard milik persona admin atau /products/{id}/edit
     * milik penjual lain) jatuh ke beranda agar juri tidak menemui 403.
     */
    private function destination(Request $request, string $persona): string
    {
        $to = (string) $request->query('to', '');
        $path = $this->internalPath($to);

        if ($path === null) {
            return $this->fallback($persona);
        }

        return $this->diizinkan($persona, $path) ? $to : $this->fallback($persona);
    }

    /**
     * Ambil path dari tujuan yang benar-benar internal, atau null bila
     * parameter `to` mencoba keluar dari aplikasi.
     */
    private function internalPath(string $to): ?string
    {
        if ($to === '' || preg_match('/[\x00-\x1F\x7F]/', $to) === 1) {
            return null;
        }

        $parts = parse_url($to);

        $isInternal = $parts !== false
            && ! isset($parts['scheme'])
            && ! isset($parts['host'])
            && str_starts_with($to, '/')
            && ! str_starts_with($to, '//')
            && ! str_contains($to, '\\');

        if (! $isInternal) {
            return null;
        }

        $path = $parts['path'] ?? '/';

        foreach (explode('/', $path) as $segmen) {
            if ($segmen === '..' || $segmen === '.') {
                return null;
            }
        }

        return $path;
    }

    /**
     * Apakah persona boleh membuka path tersebut?
     */
    private function diizinkan(string $persona, string $path): bool
    {
        if ($persona === 'admin') {
            return $path === '/admin' || str_starts_with($path, '/admin/');
        }

        foreach ((array) config('demo.safe_paths', []) as $pola) {
            if (Str::is($pola, $path)) {
                return true;
            }
        }

        return false;
    }

    private function fallback(string $persona): string
    {
        return $persona === 'admin'
            ? route('admin.dashboard', absolute: false)
            : route('home', absolute: false);
    }
}
