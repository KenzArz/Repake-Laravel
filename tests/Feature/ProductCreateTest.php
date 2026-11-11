<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');

    $this->user = User::factory()->create();
    $this->category = Category::factory()->create();
    $this->actingAs($this->user);
});

test('user can sell a product with images', function () {
    $response = $this->post(route('products.store'), [
        'judul' => 'Laptop Bekas Untuk Skripsi',
        'category_id' => $this->category->id,
        'kondisi' => 'Seperti baru',
        'harga' => 4500000,
        'deskripsi' => 'Kondisi bagus, pemakaian dua tahun, charger lengkap.',
        'kota' => 'Karawang',
        'patokan_lokasi' => 'Depan Gd. H. Oemar Said',
        'images' => [
            UploadedFile::fake()->image('satu.jpg', 800, 600),
            UploadedFile::fake()->image('dua.png', 800, 600),
        ],
    ]);

    $response->assertRedirect(route('profile.catalog'));
    $response->assertSessionHas('success');
    $response->assertSessionHasNoErrors();

    $product = Product::firstWhere('slug', 'laptop-bekas-untuk-skripsi');

    expect($product)->not->toBeNull()
        ->and($product->user_id)->toBe($this->user->id)
        ->and($product->judul)->toBe('Laptop Bekas Untuk Skripsi')
        ->and($product->harga)->toBe(4500000)
        ->and($product->kondisi)->toBe('Seperti baru')
        ->and($product->status)->toBe(Product::STATUS_DIJUAL)
        ->and($product->kota)->toBe('Karawang')
        ->and($product->patokan_lokasi)->toBe('Depan Gd. H. Oemar Said')
        ->and($product->images)->toHaveCount(2)
        ->and($product->images[0]->is_cover)->toBeTrue()
        ->and($product->images[1]->is_cover)->toBeFalse();

    Storage::disk('public')->assertExists($product->images[0]->path);
});

test('duplicate title gets a unique slug', function () {
    Product::factory()->create([
        'judul' => 'Laptop Bekas Untuk Skripsi',
        'slug' => 'laptop-bekas-untuk-skripsi',
    ]);

    $this->post(route('products.store'), [
        'judul' => 'Laptop Bekas Untuk Skripsi',
        'category_id' => $this->category->id,
        'kondisi' => 'Bekas pakai',
        'harga' => 3000000,
        'deskripsi' => 'Sama seperti produk yang sudah dipasang sebelumnya.',
        'kota' => 'Bekasi',
        'images' => [UploadedFile::fake()->image('satu.jpg')],
    ])->assertSessionHasNoErrors();

    expect(Product::firstWhere('slug', 'laptop-bekas-untuk-skripsi-2'))->not->toBeNull();
});

test('sell form is reachable and not captured by the product detail wildcard', function () {
    $response = $this->get(route('products.create'));

    $response->assertStatus(200);
    $response->assertSee('Pasang Barang');
});

test('guest cannot open the sell form', function () {
    auth()->logout();

    $this->get(route('products.create'))->assertRedirect(route('home'));
});

test('product requires kota, valid kondisi, and at least one image', function () {
    $response = $this->post(route('products.store'), [
        'judul' => 'Barang Tanpa Data Lengkap',
        'category_id' => $this->category->id,
        'kondisi' => 'bekas',
        'harga' => 100000,
        'deskripsi' => 'Deskripsi cukup panjang untuk lolos validasi.',
        'kota' => '',
        'images' => [],
    ]);

    $response->assertSessionHasErrors(['kondisi', 'kota', 'images']);
    expect(Product::count())->toBe(0);
});

test('ajax request returns json success payload', function () {
    $response = $this->post(route('products.store'), [
        'judul' => 'Barang Via Ajax',
        'category_id' => $this->category->id,
        'kondisi' => 'Seperti baru',
        'harga' => 200000,
        'deskripsi' => 'Dikirim lewat fetch untuk menjaga gambar tetap.',
        'kota' => 'Karawang',
        'images' => [UploadedFile::fake()->image('satu.jpg')],
    ], ['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json']);

    $response->assertOk()
        ->assertJson(['redirect' => route('profile.catalog')]);

    expect(Product::where('judul', 'Barang Via Ajax')->exists())->toBeTrue();
});

test('ajax request returns 422 json for invalid input', function () {
    $response = $this->post(route('products.store'), [
        'judul' => 'Barang Tidak Valid',
        'category_id' => $this->category->id,
        'kondisi' => 'Seperti baru',
        'harga' => 200000,
        'deskripsi' => 'Deskripsi cukup panjang.',
        'kota' => '',
        'images' => [UploadedFile::fake()->image('satu.jpg')],
    ], ['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json']);

    $response->assertStatus(422)
        ->assertJsonStructure(['errors' => ['kota']]);

    expect(Product::where('judul', 'Barang Tidak Valid')->exists())->toBeFalse();
});

test('home page shows category icons from the database', function () {
    Category::factory()->create([
        'name' => 'Perangkat Audio',
        'slug' => 'perangkat-audio',
        'icon' => 'https://example.com/audio.svg',
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Perangkat Audio')
        ->assertSee('https://example.com/audio.svg');
});
