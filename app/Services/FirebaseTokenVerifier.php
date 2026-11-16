<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

/**
 * Verifikasi Firebase ID token melalui Identity Toolkit.
 *
 * Dipakai bersama oleh login web (web.php) dan login API (api/v1) supaya
 * aturan akun (domain UNSIKA, email terverifikasi, tidak banned) identik.
 */
class FirebaseTokenVerifier
{
    /**
     * Verifikasi id_token dan dapatkan ringkasan akun Google/Firebase.
     *
     * @return array{ localId: string, email: string, displayName: ?string, photoUrl: ?string, emailVerified: bool }|null
     *                                                                                                                    null bila token tidak valid.
     */
    public function verify(string $idToken): ?array
    {
        try {
            $response = Http::asJson()->post(
                'https://identitytoolkit.googleapis.com/v1/accounts:lookup?key='.config('services.firebase.api_key'),
                ['idToken' => $idToken],
            )->throw();
        } catch (RequestException) {
            return null;
        }

        $account = $response->json('users.0');

        if (! is_array($account)) {
            return null;
        }

        return [
            'localId' => (string) ($account['localId'] ?? ''),
            'email' => strtolower(trim((string) ($account['email'] ?? ''))),
            'displayName' => is_string($account['displayName'] ?? null) ? $account['displayName'] : null,
            'photoUrl' => is_string($account['photoUrl'] ?? null) ? $account['photoUrl'] : null,
            'emailVerified' => (bool) ($account['emailVerified'] ?? false),
        ];
    }

    /**
     * Apakah email berasal dari domain yang diizinkan (atau subdomain-nya).
     */
    public function isAllowedDomain(string $email): bool
    {
        $domain = str($email)->after('@')->toString();

        foreach (config('services.firebase.allowed_domains', []) as $allowed) {
            $allowed = strtolower(trim((string) $allowed));

            if ($allowed !== '' && ($domain === $allowed || str_ends_with($domain, '.'.$allowed))) {
                return true;
            }
        }

        return false;
    }
}
