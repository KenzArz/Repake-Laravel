<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function productPayload(Category $category): array
{
    return [
        'judul' => 'Laptop Bekas',
        'category_id' => $category->id,
        'kondisi' => 'Dirawat dengan baik',
        'harga' => 3500000,
        'deskripsi' => 'Laptop masih sangat bagus dan dipakai ringan.',
        'kota' => 'Karawang',
    ];
}

test('tamu tidak bisa membuat produk', function () {
    $this->postJson('/api/v1/products', [])->assertStatus(401);
});

test('pengguna dapat membuat produk dengan gambar', function () {
    Storage::fake('public');
    $seller = User::factory()->create();
    Sanctum::actingAs($seller);
    $category = Category::factory()->create();

    $this->post('/api/v1/products', [
        ...productPayload($category),
        'images' => [UploadedFile::fake()->image('a.jpg', 200, 200)],
    ], ['Accept' => 'application/json'])
        ->assertCreated()
        ->assertJsonPath('data.judul', 'Laptop Bekas');

    $product = Product::sole();
    expect($product->user_id)->toBe($seller->id)
        ->and($product->status)->toBe(Product::STATUS_DIJUAL)
        ->and($product->images()->where('is_cover', true)->count())->toBe(1);
});

test('produk wajib memiliki minimal satu gambar', function () {
    $seller = User::factory()->create();
    Sanctum::actingAs($seller);

    $this->post('/api/v1/products', productPayload(Category::factory()->create()), ['Accept' => 'application/json'])
        ->assertStatus(422);

    expect(Product::count())->toBe(0);
});

test('penjual dapat memperbarui produknya', function () {
    Storage::fake('public');
    $seller = User::factory()->create();
    Sanctum::actingAs($seller);
    $product = Product::factory()->for($seller, 'seller')->create();
    ProductImage::create(['product_id' => $product->id, 'path' => 'products/a.webp', 'is_cover' => true, 'urutan' => 0]);

    $this->putJson('/api/v1/products/'.$product->id, [
        'judul' => 'Judul Baru',
        'category_id' => $product->category_id,
        'harga' => 5000000,
        'kondisi' => $product->kondisi,
        'deskripsi' => 'Deskripsi diperbarui dengan lebih panjang lagi ya.',
        'kota' => $product->kota,
    ])->assertOk()
        ->assertJsonPath('data.judul', 'Judul Baru');

    expect($product->refresh()->harga)->toBe(5000000);
});

test('bukan pemilik tidak bisa memperbarui produk', function () {
    $seller = User::factory()->create();
    $other = User::factory()->create();
    Sanctum::actingAs($other);
    $product = Product::factory()->for($seller, 'seller')->create();

    $this->putJson('/api/v1/products/'.$product->id, [
        'judul' => 'Hack',
        'category_id' => $product->category_id,
        'harga' => 1,
        'kondisi' => 'Baru',
        'deskripsi' => $product->deskripsi,
        'kota' => $product->kota,
    ])->assertStatus(403);
});

test('penjual dapat menghapus produknya (soft delete)', function () {
    $seller = User::factory()->create();
    Sanctum::actingAs($seller);
    $product = Product::factory()->for($seller, 'seller')->create();

    $this->deleteJson('/api/v1/products/'.$product->id)->assertOk();

    expect(Product::withTrashed()->find($product->id))->not->toBeNull()
        ->and(Product::find($product->id))->toBeNull();
});

test('pengguna dapat melaporkan produk orang lain', function () {
    $seller = User::factory()->create();
    $reporter = User::factory()->create();
    Sanctum::actingAs($reporter);
    $product = Product::factory()->for($seller, 'seller')->create();

    $this->postJson('/api/v1/products/'.$product->id.'/report', [
        'alasan' => 'penipuan',
        'keterangan' => 'Produk tidak sesuai deskripsi.',
    ])->assertOk();

    expect(Report::where('product_id', $product->id)->where('reporter_id', $reporter->id)->count())->toBe(1);
});

test('pemilik tidak bisa melaporkan produk sendiri', function () {
    $seller = User::factory()->create();
    Sanctum::actingAs($seller);
    $product = Product::factory()->for($seller, 'seller')->create();

    $this->postJson('/api/v1/products/'.$product->id.'/report', [
        'alasan' => 'penipuan',
        'keterangan' => 'Produk bermasalah.',
    ])->assertStatus(403);
});
