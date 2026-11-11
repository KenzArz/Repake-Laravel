<?php

use App\Models\Favorite;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $seller = User::factory()->create();
    $this->product = Product::factory()->create(['user_id' => $seller->id]);
});

test('user can view favorites page', function () {
    $response = $this->get(route('favorites.index'));

    $response->assertStatus(200);
    $response->assertSee('Produk Favorit');
});

test('user can store favorite in the favorites table', function () {
    $response = $this->postJson(route('favorites.toggle', $this->product));

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'is_favorite' => true,
            'product_id' => $this->product->id,
        ]);

    expect(
        Favorite::where('user_id', $this->user->id)
            ->where('product_id', $this->product->id)
            ->exists()
    )->toBeTrue();
});

test('user can remove favorite', function () {
    Favorite::create(['user_id' => $this->user->id, 'product_id' => $this->product->id]);

    $response = $this->postJson(route('favorites.toggle', $this->product));

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'is_favorite' => false,
            'product_id' => $this->product->id,
        ]);

    expect(
        Favorite::where('user_id', $this->user->id)
            ->where('product_id', $this->product->id)
            ->exists()
    )->toBeFalse();
});
