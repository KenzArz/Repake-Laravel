<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seller = User::factory()->create();
});

test('listing shows products from the database', function () {
    Product::factory()->create(['user_id' => $this->seller->id, 'judul' => 'Produk Listing Alpha']);

    $this->get(route('products.index'))
        ->assertOk()
        ->assertSee('Produk Listing Alpha');
});

test('listing searches by keyword', function () {
    Product::factory()->create(['user_id' => $this->seller->id, 'judul' => 'Sepeda Lipat Murah']);
    Product::factory()->create(['user_id' => $this->seller->id, 'judul' => 'Meja Belajar Kayu']);

    $this->get(route('products.index', ['q' => 'sepeda']))
        ->assertOk()
        ->assertSee('Sepeda Lipat Murah')
        ->assertDontSee('Meja Belajar Kayu');
});

test('listing filters by category', function () {
    $elektronik = Category::factory()->create(['slug' => 'elektronik', 'name' => 'Elektronik']);
    Product::factory()->create(['user_id' => $this->seller->id, 'category_id' => $elektronik->id, 'judul' => 'Kamera Tua']);

    $this->get(route('products.index', ['kategori' => 'elektronik']))
        ->assertOk()
        ->assertSee('Kamera Tua');
});
