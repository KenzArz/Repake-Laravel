<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->category = Category::factory()->create();
    Storage::fake('public');
});

function payload(array $overrides = []): array
{
    return array_merge([
        '_token' => csrf_token(),
        'judul' => 'Sepeda Lipat Tua',
        'category_id' => Category::factory()->create()->id,
        'harga' => 250000,
        'kondisi' => 'Seperti baru',
        'deskripsi' => 'Dalam kondisi bagus, ban masih tebal, rem masih kencang.',
        'kota' => 'Karawang',
        'patokan_lokasi' => 'Depan kampus',
    ], $overrides);
}

test('product create form renders with categories', function () {
    $this->actingAs($this->user)
        ->get(route('products.create'))
        ->assertOk()
        ->assertSee('Jual Barang - RePake');
});

test('authenticated user can create a product via ajax', function () {
    $file = UploadedFile::fake()->image('test.jpg', 800, 600)->size(500);

    $response = $this->actingAs($this->user)
        ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
        ->post(route('products.store'), payload([
            'category_id' => $this->category->id,
            'images' => [$file],
        ]));

    $response->assertJson(['redirect' => route('profile.catalog')]);

    $product = Product::where('user_id', $this->user->id)->first();

    expect($product)->not->toBeNull()
        ->and($product->judul)->toBe('Sepeda Lipat Tua')
        ->and($product->category->slug)->toBe($this->category->slug)
        ->and($product->status)->toBe(Product::STATUS_DIJUAL)
        ->and($product->harga)->toBe(250000);

    expect(ProductImage::where('product_id', $product->id)->count())->toBe(1)
        ->and(ProductImage::where('product_id', $product->id)->first()->is_cover)->toBeTrue();

    Storage::disk('public')->assertExists(ProductImage::where('product_id', $product->id)->first()->path);
});

test('product can be created with several images where the first one is the cover', function () {
    $files = [
        UploadedFile::fake()->image('cover.jpg', 800, 600)->size(500),
        UploadedFile::fake()->image('side.jpg', 800, 600)->size(500),
        UploadedFile::fake()->image('detail.jpg', 800, 600)->size(500),
    ];

    $this->actingAs($this->user)
        ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
        ->post(route('products.store'), payload([
            'category_id' => $this->category->id,
            'images' => $files,
        ]))
        ->assertJson(['redirect' => route('profile.catalog')]);

    $images = ProductImage::where('product_id', Product::first()->id)->orderBy('urutan')->get();

    expect($images)->toHaveCount(3)
        ->and($images->pluck('is_cover')->all())->toBe([true, false, false])
        ->and($images->pluck('urutan')->all())->toBe([0, 1, 2]);
});

test('product store rejects more than three images', function () {
    $files = collect(range(1, 4))
        ->map(fn (int $i) => UploadedFile::fake()->image("gambar-{$i}.jpg", 800, 600)->size(500))
        ->all();

    $response = $this->actingAs($this->user)
        ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
        ->post(route('products.store'), payload([
            'category_id' => $this->category->id,
            'images' => $files,
        ]));

    $response->assertStatus(422)
        ->assertJsonPath('errors.images.0', fn (string $msg): bool => str_contains($msg, 'Maksimal'));

    expect(Product::count())->toBe(0);
});

test('product store fails when no images are uploaded', function () {
    $response = $this->actingAs($this->user)
        ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
        ->post(route('products.store'), payload(['category_id' => $this->category->id]));

    $response->assertStatus(422)
        ->assertJsonPath('errors.images.0', fn (string $msg): bool => str_contains(strtolower($msg), 'gambar'));

    expect(Product::count())->toBe(0);
});

test('product store validates required fields', function () {
    $file = UploadedFile::fake()->image('test.jpg', 800, 600)->size(500);

    $response = $this->actingAs($this->user)
        ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
        ->post(route('products.store'), ['_token' => csrf_token(), 'images' => [$file]]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['judul', 'category_id', 'harga', 'deskripsi', 'kota']);
});

test('guest cannot create a product', function () {
    $this->post(route('products.store'), payload([
        'category_id' => $this->category->id,
        'images' => [UploadedFile::fake()->image('test.jpg', 800, 600)->size(500)],
    ]))->assertRedirect();

    expect(Product::count())->toBe(0);
});
