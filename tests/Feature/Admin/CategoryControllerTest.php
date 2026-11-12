<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

test('admin creates a category with an auto generated slug', function () {
    $this->actingAs($this->admin);

    $response = $this->post(route('admin.categories.store'), [
        'name' => 'Perangkat Audio',
        'icon' => '🎧',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('info');

    $category = Category::where('slug', 'perangkat-audio')->sole();
    expect($category->name)->toBe('Perangkat Audio')
        ->and($category->icon)->toBe('🎧');
});

test('category store rejects an empty payload', function () {
    $this->actingAs($this->admin);

    $response = $this->from(route('admin.dashboard', ['tab' => 'kategori']))
        ->post(route('admin.categories.store'), []);

    $response->assertRedirect(route('admin.dashboard', ['tab' => 'kategori']));
    $response->assertSessionHasErrors(['name']);
    expect(Category::count())->toBe(0);
});

test('categories index redirects to the dashboard kategori tab', function () {
    $this->actingAs($this->admin);

    $this->get(route('admin.categories.index'))
        ->assertRedirect(route('admin.dashboard', ['tab' => 'kategori']));
});

test('category store rejects a duplicate name', function () {
    $this->actingAs($this->admin);
    Category::factory()->create(['name' => 'Buku', 'slug' => 'buku']);

    $this->post(route('admin.categories.store'), ['name' => 'Buku'])
        ->assertSessionHasErrors(['name']);

    expect(Category::count())->toBe(1);
});

test('admin renames a category and the slug follows', function () {
    $this->actingAs($this->admin);
    $category = Category::factory()->create(['name' => 'Buku', 'slug' => 'buku']);

    $this->put(route('admin.categories.update', $category), ['name' => 'Buku & Majalah'])
        ->assertRedirect()
        ->assertSessionHas('info');

    expect($category->refresh()->name)->toBe('Buku & Majalah')
        ->and($category->slug)->toBe('buku-majalah');
});

test('category rename rejects a name owned by another category', function () {
    $this->actingAs($this->admin);
    Category::factory()->create(['name' => 'Elektronik', 'slug' => 'elektronik']);
    $category = Category::factory()->create(['name' => 'Buku', 'slug' => 'buku']);

    $this->put(route('admin.categories.update', $category), ['name' => 'Elektronik'])
        ->assertSessionHasErrors(['name']);

    expect($category->refresh()->name)->toBe('Buku');
});

test('deleting a category keeps its products with no category', function () {
    $this->actingAs($this->admin);
    $category = Category::factory()->create();
    $product = Product::factory()->for($category)->create();

    $this->delete(route('admin.categories.destroy', $category))
        ->assertRedirect()
        ->assertSessionHas('info');

    expect(Category::count())->toBe(0)
        ->and($product->refresh()->category_id)->toBeNull();
});

test('category store accepts an uploaded PNG icon', function () {
    Storage::fake('public');
    $this->actingAs($this->admin);

    $this->post(route('admin.categories.store'), [
        'name' => 'Perangkat Audio',
        'icon_file' => UploadedFile::fake()->image('ikon.png', 64, 64),
    ])->assertRedirect()->assertSessionHas('info');

    $category = Category::where('slug', 'perangkat-audio')->sole();

    expect($category->icon)->toStartWith('/storage/categories/');

    Storage::disk('public')->assertExists(Str::after($category->icon, '/storage/'));
});

test('category icon upload rejects a non-PNG file', function () {
    Storage::fake('public');
    $this->actingAs($this->admin);

    $this->from(route('admin.dashboard', ['tab' => 'kategori']))
        ->post(route('admin.categories.store'), [
            'name' => 'Perangkat Audio',
            'icon_file' => UploadedFile::fake()->image('ikon.jpg'),
        ])
        ->assertRedirect(route('admin.dashboard', ['tab' => 'kategori']))
        ->assertSessionHasErrors(['icon_file']);

    expect(Category::count())->toBe(0);
});

test('replacing a category icon removes the previous uploaded file', function () {
    Storage::fake('public');
    Storage::disk('public')->put('categories/lama.png', 'dummy');

    $category = Category::factory()->create([
        'name' => 'Buku',
        'icon' => '/storage/categories/lama.png',
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.categories.update', $category), [
            'name' => 'Buku',
            'icon_file' => UploadedFile::fake()->image('baru.png', 64, 64),
        ])
        ->assertRedirect()
        ->assertSessionHas('info');

    $category->refresh();

    expect($category->icon)->toStartWith('/storage/categories/')
        ->and($category->icon)->not->toBe('/storage/categories/lama.png');

    Storage::disk('public')->assertMissing('categories/lama.png');
    Storage::disk('public')->assertExists(Str::after($category->icon, '/storage/'));
});

test('deleting a category removes its uploaded icon', function () {
    Storage::fake('public');
    Storage::disk('public')->put('categories/hapus.png', 'dummy');

    $category = Category::factory()->create([
        'name' => 'Buku',
        'icon' => '/storage/categories/hapus.png',
    ]);

    $this->actingAs($this->admin)
        ->delete(route('admin.categories.destroy', $category))
        ->assertRedirect()
        ->assertSessionHas('info');

    Storage::disk('public')->assertMissing('categories/hapus.png');
});
