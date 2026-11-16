<?php

use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function makeProductWithImage(User $seller, ?Category $category = null): Product
{
    Storage::fake('public');

    $category ??= Category::factory()->create();
    $product = Product::factory()->for($seller, 'seller')->create(['category_id' => $category->id]);

    ProductImage::create([
        'product_id' => $product->id,
        'path' => 'products/test.webp',
        'is_cover' => true,
        'urutan' => 0,
    ]);

    return $product;
}

test('home mengembalikan banner, kategori, dan produk', function () {
    $seller = User::factory()->create();
    Banner::factory()->create(['is_active' => true]);
    makeProductWithImage($seller);

    $this->getJson('/api/v1/home')
        ->assertOk()
        ->assertJsonStructure([
            'data' => ['banners', 'categories', 'conditions', 'nearby', 'latest'],
        ]);
});

test('listing produk dapat difilter per kategori dan terpaginasi', function () {
    $seller = User::factory()->create();
    $elektronik = Category::factory()->create(['slug' => 'elektronik']);
    $buku = Category::factory()->create(['slug' => 'buku']);

    makeProductWithImage($seller, $elektronik);
    makeProductWithImage($seller, $buku);

    $this->getJson('/api/v1/products?kategori[]=elektronik')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('meta.total', 1);
});

test('produk dijual tampil di detail untuk tamu', function () {
    $seller = User::factory()->create();
    $product = makeProductWithImage($seller);

    $this->getJson('/api/v1/products/'.$product->id)
        ->assertOk()
        ->assertJsonPath('data.id', $product->id)
        ->assertJsonPath('data.penjual.name', $seller->name);
});

test('produk draft tersembunyi dari tamu', function () {
    $seller = User::factory()->create();
    $product = Product::factory()->for($seller, 'seller')->draft()->create();

    $this->getJson('/api/v1/products/'.$product->id)->assertStatus(404);
});

test('kategori diurutkan', function () {
    Category::factory()->create(['name' => 'Buku', 'slug' => 'buku', 'sort_order' => 2]);
    Category::factory()->create(['name' => 'Elektronik', 'slug' => 'elektronik', 'sort_order' => 1]);

    $this->getJson('/api/v1/categories')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.name', 'Elektronik');
});

test('suggestions mengembalikan produk untuk kueri', function () {
    $seller = User::factory()->create();
    makeProductWithImage($seller);

    $this->getJson('/api/v1/products/suggestions?q=')
        ->assertOk()
        ->assertJsonCount(0, 'data.products');

    $this->getJson('/api/v1/products/suggestions?q='.urlencode($seller->name))
        ->assertOk();
});
