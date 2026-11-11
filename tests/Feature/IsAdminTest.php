<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guest accessing admin area is redirected to home', function () {
    $response = $this->get(route('admin.categories.index'));

    $response->assertRedirect(route('home'));
    $this->assertGuest();
});

test('mahasiswa is forbidden from the admin area', function () {
    $this->actingAs(User::factory()->create(['role' => User::ROLE_MAHASISWA]));

    $this->get(route('admin.categories.index'))->assertForbidden();
});

test('admin can open the categories page', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->get(route('admin.categories.index'))->assertOk();
});
