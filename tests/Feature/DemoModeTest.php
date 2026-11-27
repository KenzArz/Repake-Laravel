<?php

use App\Models\Conversation;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

/**
 * Tiruan respons Firebase Identity Toolkit untuk menguji jalur login produksi.
 * Nama fungsi dibedakan dari FirebaseAuthTest agar tidak bentrok.
 */
function fakeDemoFirebaseLookup(array $overrides = []): void
{
    Http::fake([
        'identitytoolkit.googleapis.com/*' => Http::response([
            'users' => [array_merge([
                'localId' => 'demo-firebase-uid',
                'email' => 'juri.pembeli@demo.local',
                'displayName' => 'Juri Pembeli',
                'emailVerified' => true,
            ], $overrides)],
        ]),
    ]);
}

test('demo login is not found when demo mode is off', function () {
    config(['demo.enabled' => false]);

    $this->get(route('demo.login', ['persona' => 'pembeli']))->assertNotFound();
});

test('demo login rejects a persona outside the configured list', function () {
    config(['demo.enabled' => true]);
    $this->seed(DemoSeeder::class);

    $this->get(route('demo.login', ['persona' => 'penyusup']))->assertNotFound();
    $this->assertGuest();
});

test('demo login signs in the expected persona', function (string $persona, string $role) {
    config(['demo.enabled' => true]);
    $this->seed(DemoSeeder::class);

    $this->get(route('demo.login', ['persona' => $persona]))
        ->assertRedirect(route('home'));

    $user = User::query()->where('email', config("demo.personas.{$persona}"))->sole();

    $this->assertAuthenticatedAs($user);
    expect($user->role)->toBe($role)
        ->and($user->email_verified_at)->not->toBeNull();
})->with([
    ['pembeli', User::ROLE_MAHASISWA],
    ['penjual', User::ROLE_MAHASISWA],
    ['admin', User::ROLE_ADMIN],
]);

test('demo login writes the profile session used by the navbar', function () {
    config(['demo.enabled' => true]);
    $this->seed(DemoSeeder::class);

    $this->get(route('demo.login', ['persona' => 'pembeli']))->assertRedirect(route('home'));

    $profile = session('user_profile');

    expect($profile)->toBeArray()
        ->and($profile['name'])->toBe('Juri Pembeli')
        ->and($profile['email'])->toBe(config('demo.personas.pembeli'))
        ->and($profile['kota'])->not->toBeEmpty();
});

test('demo login keeps an internal destination', function () {
    config(['demo.enabled' => true]);
    $this->seed(DemoSeeder::class);

    $this->get(route('demo.login', ['persona' => 'penjual', 'to' => '/chat/1']))
        ->assertRedirect('/chat/1');
});

test('demo login never redirects to an external destination', function (string $destination) {
    config(['demo.enabled' => true]);
    $this->seed(DemoSeeder::class);

    $this->get(route('demo.login', ['persona' => 'pembeli', 'to' => $destination]))
        ->assertRedirect(route('home'));
})->with([
    'absolute url' => 'https://evil.com',
    'protocol relative' => '//evil.com',
    'backslash trick' => '/\\evil.com',
    'javascript scheme' => 'javascript:alert(1)',
]);

test('demo accounts cannot sign in through the production firebase path', function () {
    $this->seed(DemoSeeder::class);

    fakeDemoFirebaseLookup(['email' => 'juri.pembeli@demo.local']);

    $this->postJson(route('auth.firebase'), ['id_token' => 'valid-token'])
        ->assertStatus(422)
        ->assertJson(['success' => false]);

    $this->assertGuest();
});

test('demo buyer sees seeded products and a pending offer', function () {
    config(['demo.enabled' => true]);
    $this->seed(DemoSeeder::class);

    $this->get(route('demo.login', ['persona' => 'pembeli']))->assertRedirect(route('home'));

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Tumbler Corkcicle 750ml');

    $this->get(route('chat.index'))
        ->assertOk()
        ->assertSee('Tawaran menunggu keputusan')
        ->assertSee('Monitor LG 24MK430H 24 Inch IPS');
});

test('demo panel keeps the current conversation when switching persona', function () {
    config(['demo.enabled' => true]);
    $this->seed(DemoSeeder::class);

    $this->get(route('demo.login', ['persona' => 'pembeli']))->assertRedirect(route('home'));

    $conversation = Conversation::query()->orderBy('id')->firstOrFail();

    $this->get(route('chat.show', ['conversation' => $conversation]))
        ->assertOk()
        ->assertSee('demo/masuk/penjual?to='.urlencode('/chat/'.$conversation->id));
});

test('demo reset is only reachable by the admin persona in demo mode', function () {
    config(['demo.enabled' => false]);
    $this->post(route('demo.reset'))->assertNotFound();

    config(['demo.enabled' => true]);
    $this->seed(DemoSeeder::class);

    $this->get(route('demo.login', ['persona' => 'pembeli']));

    $this->post(route('demo.reset'))->assertForbidden();
});

test('demo panel and juri login block only render when demo mode is on', function () {
    config(['demo.enabled' => true]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Mode Demo Penilaian')
        ->assertSee('Masuk sebagai Juri')
        ->assertDontSee('Continue with Google');

    config(['demo.enabled' => false]);

    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('Mode Demo Penilaian')
        ->assertDontSee('Masuk sebagai Juri')
        ->assertSee('Continue with Google')
        ->assertSee('Buka demo penilaian');
});
