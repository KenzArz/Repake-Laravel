<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function fakeFirebaseLookup(array $overrides = [], int $status = 200): void
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

test('student unsika account can login and user is created', function () {
    fakeFirebaseLookup();

    $response = $this->postJson(route('auth.firebase'), ['id_token' => 'valid-token']);

    $response->assertOk()->assertJson([
        'success' => true,
        'redirect' => route('home'),
    ]);

    $this->assertAuthenticated();

    $user = User::sole();
    expect($user->firebase_uid)->toBe('firebase-uid-123')
        ->and($user->email)->toBe('aldi@student.unsika.ac.id')
        ->and($user->avatar)->toBe('https://example.com/avatar.png')
        ->and($user->email_verified_at)->not->toBeNull();
});

test('staff unsika account can login', function () {
    fakeFirebaseLookup(['email' => 'dosen@unsika.ac.id']);

    $this->postJson(route('auth.firebase'), ['id_token' => 'valid-token'])
        ->assertOk()
        ->assertJson(['success' => true]);

    $this->assertAuthenticated();
    expect(auth()->user()->email)->toBe('dosen@unsika.ac.id');
});

test('gmail account is rejected', function () {
    fakeFirebaseLookup(['email' => 'aldi@gmail.com']);

    $this->postJson(route('auth.firebase'), ['id_token' => 'valid-token'])
        ->assertStatus(422)
        ->assertJson(['success' => false]);

    $this->assertGuest();
    expect(User::count())->toBe(0);
});

test('similar but different domain is rejected', function () {
    fakeFirebaseLookup(['email' => 'aldi@notunsika.ac.id']);

    $this->postJson(route('auth.firebase'), ['id_token' => 'valid-token'])
        ->assertStatus(422);

    $this->assertGuest();
});

test('domain suffix spoofing is rejected', function () {
    fakeFirebaseLookup(['email' => 'aldi@unsika.ac.id.evil.com']);

    $this->postJson(route('auth.firebase'), ['id_token' => 'valid-token'])
        ->assertStatus(422);

    $this->assertGuest();
});

test('unverified email is rejected', function () {
    fakeFirebaseLookup(['emailVerified' => false]);

    $this->postJson(route('auth.firebase'), ['id_token' => 'valid-token'])
        ->assertStatus(422);

    $this->assertGuest();
});

test('second login with same firebase uid does not duplicate user and keeps edited name', function () {
    fakeFirebaseLookup();

    $this->postJson(route('auth.firebase'), ['id_token' => 'valid-token'])->assertOk();

    $user = User::sole();
    $user->update(['name' => 'Nama Sudah Diedit']);

    $this->post(route('logout'));

    $this->postJson(route('auth.firebase'), ['id_token' => 'valid-token'])->assertOk();

    expect(User::count())->toBe(1);
    expect(User::sole()->name)->toBe('Nama Sudah Diedit');
});

test('invalid firebase token returns 401 and stays guest', function () {
    Http::fake([
        'identitytoolkit.googleapis.com/*' => Http::response(['error' => ['message' => 'INVALID_ID_TOKEN']], 400),
    ]);

    $this->postJson(route('auth.firebase'), ['id_token' => 'invalid-token'])
        ->assertStatus(401)
        ->assertJson(['success' => false]);

    $this->assertGuest();
});

test('same local part from different allowed domains gets unique username', function () {
    User::create([
        'name' => 'Aldi Staf',
        'email' => 'aldi@unsika.ac.id',
        'username' => 'aldi',
    ]);

    fakeFirebaseLookup(['localId' => 'uid-2', 'email' => 'aldi@student.unsika.ac.id']);

    $this->postJson(route('auth.firebase'), ['id_token' => 'valid-token'])->assertOk();

    expect(User::where('firebase_uid', 'uid-2')->value('username'))->toBe('aldi_1');
});

test('id token is required', function () {
    $this->postJson(route('auth.firebase'), [])
        ->assertStatus(422)
        ->assertJsonValidationErrors('id_token');
});

test('guest cannot open profile pages', function () {
    $this->get(route('profile.catalog'))->assertRedirect(route('home'));
    $this->get(route('profile.edit'))->assertRedirect(route('home'));
    $this->put(route('profile.update'), ['name' => 'Test'])->assertRedirect(route('home'));

    $this->assertGuest();
});

test('guest cannot open favorites pages', function () {
    $this->get(route('favorites.index'))->assertRedirect(route('home'));
    $this->post(route('favorites.toggle', 1))->assertRedirect(route('home'));

    expect(session('favorites'))->toBeNull();
});

test('user can logout', function () {
    fakeFirebaseLookup();

    $this->postJson(route('auth.firebase'), ['id_token' => 'valid-token'])->assertOk();
    $this->assertAuthenticated();

    $this->post(route('logout'))->assertRedirect(route('home'));

    $this->assertGuest();
    expect(session('user_profile'))->toBeNull();
});

test('user with admin role is granted access and redirected to dashboard', function () {
    $admin = User::factory()->admin()->create([
        'email' => 'admin@unsika.ac.id',
        'firebase_uid' => 'admin-uid-existing',
    ]);

    fakeFirebaseLookup([
        'localId' => 'admin-uid',
        'email' => 'admin@unsika.ac.id',
        'displayName' => 'Admin RePake',
    ]);

    $this->postJson(route('auth.firebase'), ['id_token' => 'valid-token'])
        ->assertOk()
        ->assertJson([
            'success' => true,
            'redirect' => route('admin.dashboard'),
        ]);

    $this->assertAuthenticated();

    $user = User::sole();
    expect($user->role)->toBe(User::ROLE_ADMIN)
        ->and($user->isAdmin())->toBeTrue();
});

test('existing admin row without firebase uid is linked by email on first login', function () {
    $admin = User::factory()->admin()->create(['firebase_uid' => null, 'email' => 'admin@unsika.ac.id']);

    fakeFirebaseLookup([
        'localId' => 'admin-uid',
        'email' => 'admin@unsika.ac.id',
    ]);

    $this->postJson(route('auth.firebase'), ['id_token' => 'valid-token'])
        ->assertOk()
        ->assertJson(['success' => true, 'redirect' => route('admin.dashboard')]);

    expect(User::count())->toBe(1)
        ->and($admin->refresh()->firebase_uid)->toBe('admin-uid')
        ->and($admin->role)->toBe(User::ROLE_ADMIN);
});
