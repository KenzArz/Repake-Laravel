<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function apiFakeFirebase(array $overrides = [], int $status = 200): void
{
    Http::fake([
        'identitytoolkit.googleapis.com/*' => Http::response([
            'users' => [array_merge([
                'localId' => 'firebase-uid-123',
                'email' => 'aldi@student.unsika.ac.id',
                'displayName' => 'Aldi Pratama',
                'photoUrl' => 'https://example.com/avatar.png',
                'emailVerified' => true,
            ], $overrides)],
        ], $status),
    ]);
}

test('firebase token dapat ditukar menjadi sanctum token', function () {
    apiFakeFirebase();

    $this->postJson('/api/v1/auth/firebase', ['id_token' => 'valid-token'])
        ->assertOk()
        ->assertJsonStructure([
            'data' => ['token', 'token_type', 'user' => ['id', 'email']],
        ])
        ->assertJsonPath('data.token_type', 'Bearer')
        ->assertJsonPath('data.user.email', 'aldi@student.unsika.ac.id');

    expect(User::count())->toBe(1);
});

test('token invalid mengembalikan 401', function () {
    Http::fake([
        'identitytoolkit.googleapis.com/*' => Http::response(['error' => ['message' => 'INVALID_ID_TOKEN']], 400),
    ]);

    $this->postJson('/api/v1/auth/firebase', ['id_token' => 'invalid'])
        ->assertStatus(401)
        ->assertJsonPath('data', null);
});

test('akun di luar domain unsika ditolak', function () {
    apiFakeFirebase(['email' => 'aldi@gmail.com']);

    $this->postJson('/api/v1/auth/firebase', ['id_token' => 'valid'])
        ->assertStatus(422);

    expect(User::count())->toBe(0);
});

test('id_token wajib diisi', function () {
    $this->postJson('/api/v1/auth/firebase', [])->assertStatus(422);
});

test('akun dibanned tidak bisa login', function () {
    User::factory()->banned()->create([
        'email' => 'aldi@student.unsika.ac.id',
        'firebase_uid' => 'firebase-uid-123',
    ]);

    apiFakeFirebase();

    $this->postJson('/api/v1/auth/firebase', ['id_token' => 'valid'])->assertStatus(403);
});

test('logout mencabut token aktif', function () {
    apiFakeFirebase();

    $token = $this->postJson('/api/v1/auth/firebase', ['id_token' => 'valid'])->json('data.token');

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/me')
        ->assertOk();

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/auth/logout')
        ->assertOk();

    expect(User::sole()->tokens()->count())->toBe(0);
});

test('endpoint terautentikasi menolak tanpa token', function () {
    $this->getJson('/api/v1/me')->assertStatus(401);
});

test('akun dibanned ditolak akses terautentikasi', function () {
    $user = User::factory()->banned()->create();
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/me')->assertStatus(403);
});
