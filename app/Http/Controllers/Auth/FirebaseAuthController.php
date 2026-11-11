<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class FirebaseAuthController extends Controller
{
    /**
     * Verifikasi ID token Firebase lalu buat session login Laravel.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'id_token' => ['required', 'string'],
        ]);

        try {
            $response = Http::asJson()->post(
                'https://identitytoolkit.googleapis.com/v1/accounts:lookup?key='.config('services.firebase.api_key'),
                ['idToken' => $request->string('id_token')->toString()]
            )->throw();
        } catch (RequestException) {
            return response()->json([
                'success' => false,
                'message' => 'Token login tidak valid. Silakan coba lagi.',
            ], 401);
        }

        $account = $response->json('users.0');
        $email = is_array($account) ? strtolower(trim((string) ($account['email'] ?? ''))) : '';

        if (! is_array($account) || empty($account['localId']) || $email === '' || ($account['emailVerified'] ?? false) !== true) {
            return $this->reject($request);
        }

        if (! $this->isAllowedDomain($email)) {
            return $this->reject($request);
        }

        $user = User::firstOrNew(['firebase_uid' => $account['localId']]);

        if (! $user->exists) {
            $user->name = $account['displayName']
                ?? str($email)->before('@')->replace('.', ' ')->title()->toString();
            $user->username = $this->uniqueUsername($email);
        }

        $user->email = $email;
        $user->avatar = $account['photoUrl'] ?? $user->avatar;
        $user->email_verified_at ??= now();
        $user->save();

        Auth::login($user, true);
        $request->session()->regenerate();

        $request->session()->put('user_profile', [
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
            'kota' => $user->kota ?: 'Karawang',
            'avatar' => $user->avatar,
            'is_verified' => true,
        ]);

        return response()->json([
            'success' => true,
            'redirect' => route('home'),
        ]);
    }

    /**
     * Logout pengguna dan bersihkan session.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->forget(['auth_user', 'user_profile']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', 'Anda telah keluar dari akun.');
    }

    /**
     * Tolak login dan pastikan tidak ada session aktif.
     */
    private function reject(Request $request): JsonResponse
    {
        Auth::logout();
        $request->session()->forget(['auth_user', 'user_profile']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => false,
            'message' => 'Akses ditolak: gunakan akun Google UNSIKA yang sudah terverifikasi.',
        ], 422);
    }

    /**
     * Pastikan domain email sama dengan atau subdomain dari domain yang diizinkan.
     */
    private function isAllowedDomain(string $email): bool
    {
        $domain = (string) str($email)->after('@');

        foreach (config('services.firebase.allowed_domains', []) as $allowed) {
            $allowed = strtolower(trim($allowed));

            if ($allowed !== '' && ($domain === $allowed || str_ends_with($domain, '.'.$allowed))) {
                return true;
            }
        }

        return false;
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
