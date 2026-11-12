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
    $response = $this->postJson(route('favorites.toggle', $this->product->id));

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

    $response = $this->postJson(route('favorites.toggle', $this->product->id));

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

test('favorites page hides products that are no longer for sale', function () {
    $this->product->update(['status' => Product::STATUS_DITANGGUIHKAN]);
    Favorite::create(['user_id' => $this->user->id, 'product_id' => $this->product->id]);

    $sold = Product::factory()->terjual()->create([
        'user_id' => $this->product->user_id,
        'judul' => 'Barang sudah laku',
    ]);
    Favorite::create(['user_id' => $this->user->id, 'product_id' => $sold->id]);

    $response = $this->get(route('favorites.index'));

    $response->assertStatus(200);
    $response->assertDontSee($this->product->judul);
    $response->assertDontSee('Barang sudah laku');
    $response->assertSee('belum menandai produk apapun');
});

test('favorites page hides products from banned sellers', function () {
    $this->product->user->forceFill(['is_banned' => true])->save();
    Favorite::create(['user_id' => $this->user->id, 'product_id' => $this->product->id]);

    $response = $this->get(route('favorites.index'));

    $response->assertStatus(200);
    $response->assertDontSee($this->product->judul);
});

test('favoritedProducts relation returns the favorited products', function () {
    Favorite::create(['user_id' => $this->user->id, 'product_id' => $this->product->id]);

    expect($this->user->favoritedProducts()->pluck('products.id')->all())
        ->toBe([$this->product->id]);
});
