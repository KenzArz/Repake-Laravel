<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\FirebaseTokenVerifier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FirebaseAuthController extends Controller
{
    public function __construct(private readonly FirebaseTokenVerifier $verifier) {}

    /**
     * Verifikasi ID token Firebase lalu buat session login Laravel.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'id_token' => ['required', 'string'],
        ]);

        $account = $this->verifier->verify($request->string('id_token')->toString());

        if ($account === null) {
            return $this->reject($request, 401);
        }

        $email = $account['email'];

        if ($email === '' || $account['emailVerified'] !== true || ! $this->verifier->isAllowedDomain($email)) {
            return $this->reject($request);
        }

        $user = User::query()
            ->where(function (Builder $query) use ($account, $email): void {
                $query->where('firebase_uid', $account['localId'])
                    ->orWhere('email', $email);
            })
            ->first() ?? new User;

        if ($user->exists && $user->is_banned) {
            return $this->reject($request);
        }

        if (! $user->exists) {
            $user->name = $account['displayName']
                ?? str($email)->before('@')->replace('.', ' ')->title()->toString();
            $user->username = $this->uniqueUsername($email);
        }

        $user->firebase_uid = $account['localId'];
        $user->email = $email;
        $user->email_verified_at ??= now();
        $user->role = $user->isAdmin() ? User::ROLE_ADMIN : User::ROLE_MAHASISWA;

        $hasCustomAvatar = filled($user->avatar)
            && ! str_starts_with($user->avatar, 'http://')
            && ! str_starts_with($user->avatar, 'https://');

        if (! $user->exists) {
            $user->avatar = $account['photoUrl'] ?? null;
        } elseif (! $hasCustomAvatar && filled($user->avatar)) {
            $user->avatar = $account['photoUrl'] ?? $user->avatar;
        }

        $user->save();

        Auth::login($user, true);
        $request->session()->regenerate();

        $request->session()->put('user_profile', [
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
            'kota' => $user->kota,
            'avatar' => $user->avatar_url,
            'is_verified' => true,
        ]);

        return response()->json([
            'success' => true,
            'redirect' => $user->isAdmin() ? route('admin.dashboard') : route('home'),
        ]);
    }

    /**
     * Logout pengguna dan bersihkan session.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->forget(['auth_user', 'user_profile', 'user_location']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', 'Anda telah keluar dari akun.');
    }

    /**
     * Tolak login dan pastikan tidak ada session aktif.
     */
    private function reject(Request $request, int $status = 422): JsonResponse
    {
        Auth::logout();
        $request->session()->forget(['auth_user', 'user_profile']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => false,
            'message' => 'Akses ditolak: gunakan akun Google UNSIKA yang sudah terverifikasi.',
        ], $status);
    }

    /**
     * Buat username unik dari bagian lokal email.
     */
    private function uniqueUsername(string $email): string
    {
        $base = str($email)->before('@')->replace('.', '_')->lower()->toString();
        $username = $base;
        $suffix = 1;

        while (User::where('username', $username)->exists()) {
            $username = $base.'_'.$suffix++;
        }

        return $username;
    }
}
