<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

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

    $response = $this->from(route('admin.categories.index'))
        ->post(route('admin.categories.store'), []);

    $response->assertRedirect(route('admin.categories.index'));
    $response->assertSessionHasErrors(['name']);
    expect(Category::count())->toBe(0);
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
