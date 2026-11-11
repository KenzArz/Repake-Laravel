<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('banned user is logged out and redirected home with an explanation', function () {
    $this->actingAs(User::factory()->banned()->create());

    $response = $this->get(route('favorites.index'));

    $response->assertRedirect(route('home'));
    $this->assertGuest();
    $response->assertSessionHas('info');
});

test('banned user cannot log in through firebase', function () {
    Http::fake([
        'identitytoolkit.googleapis.com/*' => Http::response([
            'users' => [[
                'localId' => 'firebase-uid-banned',
                'email' => 'banned@student.unsika.ac.id',
                'emailVerified' => true,
            ]],
        ], 200),
    ]);

    $user = User::factory()->banned()->create([
        'email' => 'banned@student.unsika.ac.id',
        'firebase_uid' => 'firebase-uid-banned',
    ]);

    $response = $this->postJson(route('auth.firebase'), ['id_token' => 'valid-token']);

    $response->assertStatus(422);
    $this->assertGuest();
    expect($user->refresh()->is_banned)->toBeTrue();
});
