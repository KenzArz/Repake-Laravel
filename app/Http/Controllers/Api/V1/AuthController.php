<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\FirebaseTokenVerifier;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private readonly FirebaseTokenVerifier $verifier) {}

    /**
     * Tukar Firebase ID token menjadi Sanctum token.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate(['id_token' => ['required', 'string']]);

        $account = $this->verifier->verify($request->string('id_token')->toString());

        if ($account === null) {
            return ApiResponse::error('Token login tidak valid. Silakan coba lagi.', 401);
        }

        $email = $account['email'];

        if ($email === '' || $account['emailVerified'] !== true || ! $this->verifier->isAllowedDomain($email)) {
            return ApiResponse::error('Akses ditolak: gunakan akun Google UNSIKA yang sudah terverifikasi.', 422);
        }

        $user = User::query()
            ->where(function (Builder $query) use ($account, $email): void {
                $query->where('firebase_uid', $account['localId'])
                    ->orWhere('email', $email);
            })
            ->first() ?? new User;

        if ($user->exists && $user->is_banned) {
            return ApiResponse::error('Akun Anda diblokir. Hubungi admin RePake.', 403);
        }

        if (! $user->exists) {
            $user->name = $account['displayName']
                ?? str($email)->before('@')->replace('.', ' ')->title()->toString();
            $user->username = $this->uniqueUsername($email);
            $user->avatar = $account['photoUrl'] ?? null;
        }

        $user->firebase_uid = $account['localId'];
        $user->email = $email;
        $user->email_verified_at ??= now();
        $user->role = $user->isAdmin() ? User::ROLE_ADMIN : User::ROLE_MAHASISWA;
        $user->save();

        $device = trim((string) $request->input('device_name', 'mobile'));
        $token = $user->createToken($device !== '' ? $device : 'mobile', ['*']);

        return ApiResponse::success([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
        ], 'Berhasil masuk.');
    }

    /**
     * Cabut token saat ini.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return ApiResponse::success(null, 'Berhasil keluar.');
    }

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
