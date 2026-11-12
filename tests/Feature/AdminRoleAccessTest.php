<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user with role admin can access admin dashboard', function () {
    $admin = User::factory()->create([
        'email' => 'admin@test.local',
        'role' => User::ROLE_ADMIN,
        'password' => bcrypt('password'),
    ]);

    $this->actingAs($admin);

    $this->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Laporan');
});

test('user with role mahasiswa cannot access admin dashboard', function () {
    $student = User::factory()->create([
        'email' => 'student@test.local',
        'role' => User::ROLE_MAHASISWA,
        'password' => bcrypt('password'),
    ]);

    $this->actingAs($student);

    $this->get(route('admin.dashboard'))
        ->assertForbidden();
});

test('banned user is redirected even if role is admin', function () {
    $admin = User::factory()->create([
        'email' => 'admin@test.local',
        'role' => User::ROLE_ADMIN,
        'password' => bcrypt('password'),
        'is_banned' => true,
    ]);

    $this->actingAs($admin);

    $this->get(route('admin.dashboard'))
        ->assertRedirect(route('home'))
        ->assertSessionHas('info');
});

test('isAdmin returns true when role is admin', function () {
    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    expect($user->isAdmin())->toBeTrue();
});

test('isAdmin returns false when role is mahasiswa', function () {
    $user = User::factory()->create(['role' => User::ROLE_MAHASISWA]);
    expect($user->isAdmin())->toBeFalse();
});

test('new users get the default mahasiswa role', function () {
    $user = User::factory()->create();

    expect($user->refresh()->role)->toBe(User::ROLE_MAHASISWA)
        ->and($user->isAdmin())->toBeFalse();
});
