<?php

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('toggle menambah lalu menghapus favorit', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    $product = Product::factory()->create();

    $this->postJson('/api/v1/favorites/'.$product->id.'/toggle')
        ->assertOk()
        ->assertJsonPath('data.is_favorite', true);

    expect($user->favorites()->count())->toBe(1);

    $this->postJson('/api/v1/favorites/'.$product->id.'/toggle')
        ->assertOk()
        ->assertJsonPath('data.is_favorite', false);

    expect($user->favorites()->count())->toBe(0);
});

test('daftar favorit menampilkan produk favorit', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    $product = Product::factory()->create();

    $user->favorites()->create(['product_id' => $product->id]);

    $this->getJson('/api/v1/favorites')
        ->assertOk()
        ->assertJsonCount(1, 'data.products')
        ->assertJsonPath('data.count', 1);
});
