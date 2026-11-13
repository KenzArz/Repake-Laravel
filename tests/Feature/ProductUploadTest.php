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

test('uploaded image is converted to webp and downscaled', function () {
    $this->actingAs($this->user)
        ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
        ->post(route('products.store'), payload([
            'category_id' => $this->category->id,
            'images' => [UploadedFile::fake()->image('besar.jpg', 3000, 2000)],
        ]))
        ->assertOk();

    $image = ProductImage::firstOrFail();

    expect($image->path)->toEndWith('.webp');

    $info = getimagesize(Storage::disk('public')->path($image->path));

    expect($info)->not->toBeFalse()
        ->and(max($info[0], $info[1]))->toBeLessThanOrEqual(1920);
});

test('product update can replace every image', function () {
    $product = Product::factory()->create([
        'user_id' => $this->user->id,
        'category_id' => $this->category->id,
    ]);

    $old = $product->images()->create([
        'path' => 'products/lama.webp',
        'urutan' => 0,
        'is_cover' => true,
    ]);

    Storage::disk('public')->put('products/lama.webp', 'gambar lama');

    $this->actingAs($this->user)
        ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
        ->put(route('products.update', $product), payload([
            'category_id' => $this->category->id,
            'judul' => $product->judul,
            'delete_images' => [$old->id],
            'images' => [UploadedFile::fake()->image('baru.jpg', 800, 600)],
        ]))
        ->assertOk()
        ->assertJson(['redirect' => $product->detailUrl()]);

    $images = $product->images()->get();

    expect($images)->toHaveCount(1)
        ->and($images->first()->path)->not->toBe('products/lama.webp')
        ->and($images->first()->path)->not->toBeNull()
        ->and($images->first()->is_cover)->toBeTrue();

    Storage::disk('public')->assertExists($images->first()->path);
    Storage::disk('public')->assertMissing('products/lama.webp');
});

test('product update rejects removing every image without a replacement', function () {
    $product = Product::factory()->create([
        'user_id' => $this->user->id,
        'category_id' => $this->category->id,
    ]);

    $old = $product->images()->create([
        'path' => 'products/lama.webp',
        'urutan' => 0,
        'is_cover' => true,
    ]);

    Storage::disk('public')->put('products/lama.webp', 'gambar lama');

    $this->actingAs($this->user)
        ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
        ->put(route('products.update', $product), payload([
            'category_id' => $this->category->id,
            'judul' => 'Judul Yang Tidak Boleh Tersimpan',
            'delete_images' => [$old->id],
        ]))
        ->assertSessionHasErrors('images');

    expect($product->fresh()->judul)->not->toBe('Judul Yang Tidak Boleh Tersimpan')
        ->and($product->images()->count())->toBe(1);

    Storage::disk('public')->assertExists('products/lama.webp');
});

test('product update can add images to a product without any image', function () {
    $product = Product::factory()->create([
        'user_id' => $this->user->id,
        'category_id' => $this->category->id,
    ]);

    expect($product->images()->count())->toBe(0);

    $this->actingAs($this->user)
        ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
        ->put(route('products.update', $product), payload([
            'category_id' => $this->category->id,
            'judul' => $product->judul,
            'images' => [UploadedFile::fake()->image('pulih.jpg', 800, 600)],
        ]))
        ->assertOk();

    $image = $product->images()->firstOrFail();

    expect($image->path)->toEndWith('.webp')
        ->and($image->is_cover)->toBeTrue();

    Storage::disk('public')->assertExists($image->path);
});

test('upload with php upload error reports the file name', function () {
    $fake = UploadedFile::fake()->image('rusak.jpg', 800, 600);

    $broken = new UploadedFile(
        $fake->getPathname(),
        'rusak.jpg',
        'image/jpeg',
        UPLOAD_ERR_INI_SIZE,
        true,
    );

    $response = $this->actingAs($this->user)
        ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
        ->post(route('products.store'), payload([
            'category_id' => $this->category->id,
            'images' => [$broken],
        ]));

    $response->assertStatus(422);
    expect(json_encode($response->json('errors')))
        ->toContain('rusak.jpg');
});

test('fake jpg with non-image content is rejected with format message', function () {
    $path = tempnam(sys_get_temp_dir(), 'palsu');
    file_put_contents($path, 'bukan gambar');

    $file = new UploadedFile($path, 'palsu.jpg', null, null, true);

    $response = $this->actingAs($this->user)
        ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
        ->post(route('products.store'), payload([
            'category_id' => $this->category->id,
            'images' => [$file],
        ]));

    $response->assertStatus(422);
    expect(json_encode($response->json('errors')))
        ->toContain('Format gambar harus JPG, PNG, atau WebP.');
});
