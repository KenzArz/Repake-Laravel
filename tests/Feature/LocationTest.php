<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guest cannot save a detected location', function () {
    $this->postJson(route('location.store'), ['kota' => 'Karawang'])
        ->assertUnauthorized();

    expect(User::where('kota', 'Karawang')->count())->toBe(0);
});

test('a logged-in user saves the detected city', function () {
    $user = User::factory()->create(['kota' => null]);

    $this->actingAs($user)
        ->postJson(route('location.store'), ['kota' => 'Karawang'])
        ->assertOk()
        ->assertJsonPath('kota', 'Karawang');

    expect($user->refresh()->kota)->toBe('Karawang');
});

test('location needs a city name', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('location.store'), [])
        ->assertSessionHasErrors(['kota']);
});

test('location rejects an overly long city name', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('location.store'), ['kota' => str_repeat('a', 101)])
        ->assertSessionHasErrors(['kota']);
});
