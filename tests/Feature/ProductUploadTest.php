<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    Storage::fake('public');
});

test('product create form renders with categories', function () {
    $this->actingAs($this->user)
        ->get(route('products.create'))
        ->assertOk()
        ->assertSee('Jual Barang - RePake');
});

test('authenticated user can create a product via ajax', function () {
    $category = Category::factory()->create();

    $file = UploadedFile::fake()->image('test.jpg', 800, 600)->size(500); // 500KB

    $response = $this->actingAs($this->user)
        ->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'application/json',
        ])
        ->post(route('products.store'), [
            '_token' => csrf_token(),
            'judul' => 'Sepeda Lipat Tua',
            'category_id' => $category->id,
            'harga' => 250000,
            'kondisi' => 'Seperti baru',
            'deskripsi' => 'Dalam kondisi bagus, ban masih tebal, rem masih kencang.',
            'kota' => 'Karawang',
            'patokan_lokasi' => 'Depan kampus',
            'images' => [$file],
        ]);

    $response->assertJson(['redirect' => route('profile.catalog')]);

    Storage::disk('public')->assertExists('products/');
    expect(Product::where('user_id', $this->user->id)->count())->toBe(1);

    $product = Product::where('user_id', $this->user->id)->first();
    expect($product->judul)->toBe('Sepeda Lipat Tua')
        ->and($product->category->slug)->toBe($category->slug)
        ->and($product->status)->toBe(Product::STATUS_DIJUAL);
});

test('product store fails when no images are uploaded', function () {
    $category = Category::factory()->create();

    $response = $this->actingAs($this->user)
        ->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'application/json',
        ])
        ->post(route('products.store'), [
            '_token' => csrf_token(),
            'judul' => 'Produk Tanpa Gambar',
            'category_id' => $category->id,
            'harga' => 100000,
            'kondisi' => 'Seperti baru',
            'deskripsi' => 'Deskripsi lengkap untuk produk ini.',
            'kota' => 'Karawang',
        ]);

    $response->assertStatus(422)
        ->assertJsonPath('errors.images.0', fn (string $msg): bool => str_contains(strtolower($msg), 'gambar'));
});

test('product store validates required fields', function () {
    $file = UploadedFile::fake()->image('test.jpg', 800, 600)->size(500);

    $response = $this->actingAs($this->user)
        ->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'application/json',
        ])
        ->post(route('products.store'), [
            '_token' => csrf_token(),
            'images' => [$file],
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['judul', 'category_id', 'harga', 'deskripsi', 'kota']);
});
