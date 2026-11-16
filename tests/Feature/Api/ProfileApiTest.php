<?php

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('/me mengembalikan profil dan statistik', function () {
    $user = User::factory()->create(['kota' => 'Karawang']);
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('data.user.id', $user->id)
        ->assertJsonPath('data.user.kota', 'Karawang')
        ->assertJsonStructure(['data' => ['stats' => ['rating', 'reviews_count', 'sold_count']]]);
});

test('profil dapat diperbarui', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->patchJson('/api/v1/me', ['name' => 'Nama Baru', 'bio' => 'Halo'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Nama Baru');

    expect($user->refresh()->name)->toBe('Nama Baru');
});

test('lokasi dapat disimpan', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->putJson('/api/v1/me/location', ['kota' => 'Bekasi'])
        ->assertOk()
        ->assertJsonPath('data.kota', 'Bekasi');

    expect($user->refresh()->kota)->toBe('Bekasi');
});

test('profil publik penjual menampilkan produk', function () {
    $seller = User::factory()->create(['username' => 'penjualku']);
    Product::factory()->for($seller, 'seller')->create();

    $this->getJson('/api/v1/sellers/penjualku')
        ->assertOk()
        ->assertJsonCount(1, 'data.products')
        ->assertJsonPath('data.user.username', 'penjualku');
});

test('profil penjual yang dibanned tidak bisa diakses', function () {
    $seller = User::factory()->banned()->create();

    $this->getJson('/api/v1/sellers/'.$seller->id)->assertStatus(404);
});
