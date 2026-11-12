<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guest accessing admin area is redirected to home', function () {
    $response = $this->get(route('admin.dashboard'));

    $response->assertRedirect(route('home'));
    $this->assertGuest();
});

test('mahasiswa is forbidden from the admin area', function () {
    $this->actingAs(User::factory()->create(['role' => User::ROLE_MAHASISWA]));

    $this->get(route('admin.dashboard'))->assertForbidden();
});

test('role admin without an allowlisted email is forbidden', function () {
    $this->actingAs(User::factory()->create([
        'email' => 'admin@unsika.ac.id',
        'role' => User::ROLE_ADMIN,
    ]));

    $this->get(route('admin.dashboard'))->assertForbidden();
});

test('allowlisted email can open the admin dashboard', function () {
    $admin = User::factory()->create([
        'email' => '2410631170004@student.unsika.ac.id',
        'role' => User::ROLE_MAHASISWA,
    ]);

    expect($admin->isAdmin())->toBeTrue();

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Laporan')
        ->assertSee('Kategori')
        ->assertSee('Banner');
});

test('admin email match is case insensitive', function () {
    $admin = User::factory()->create([
        'email' => '2410631170004@STUDENT.UNSIKA.AC.ID',
        'role' => User::ROLE_MAHASISWA,
    ]);

    expect($admin->isAdmin())->toBeTrue();
});
