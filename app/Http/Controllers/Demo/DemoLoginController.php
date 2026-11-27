<?php

namespace App\Http\Controllers\Demo;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        return redirect($this->destination($request));
    }

    /**
     * Halaman tujuan setelah login. Hanya path internal yang diterima supaya
     * parameter `to` tidak bisa dipakai mengalihkan juri ke domain lain.
     */
    private function destination(Request $request): string
    {
        $to = (string) $request->query('to', '');
        $parts = parse_url($to);

        $isInternal = $parts !== false
            && ! isset($parts['scheme'])
            && ! isset($parts['host'])
            && str_starts_with($to, '/')
            && ! str_starts_with($to, '//')
            && ! str_contains($to, '\\');

        return $isInternal ? $to : route('home');
    }
}
