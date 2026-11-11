<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('home lists products and categories from the database', function () {
    $seller = User::factory()->create();
    $category = Category::factory()->create();
    $product = Product::factory()->create([
        'user_id' => $seller->id,
        'category_id' => $category->id,
        'judul' => 'Produk Nyata Dari Database',
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Produk Nyata Dari Database')
        ->assertSee($category->name);
});

test('home filters products by active category', function () {
    $seller = User::factory()->create();
    $elektronik = Category::factory()->create(['slug' => 'elektronik', 'name' => 'Elektronik']);
    $pakaian = Category::factory()->create(['slug' => 'pakaian', 'name' => 'Pakaian']);

    Product::factory()->create(['user_id' => $seller->id, 'category_id' => $elektronik->id, 'judul' => 'Laptop Unik']);
    Product::factory()->create(['user_id' => $seller->id, 'category_id' => $pakaian->id, 'judul' => 'Jaket Unik']);

    $this->get(route('home', ['kategori' => 'elektronik']))
        ->assertOk()
        ->assertSee('Laptop Unik')
        ->assertDontSee('Jaket Unik');
});

test('home lists categories in the configured display order', function () {
    $urutan = ['Elektronik', 'Pakaian', 'Perabot', 'Olahraga', 'Buku', 'Hobi', 'Otomotif', 'Lainnya'];

    foreach ($urutan as $i => $name) {
        Category::factory()->create([
            'name' => $name,
            'slug' => Str::slug($name),
            'sort_order' => $i + 1,
        ]);
    }

    $response = $this->get(route('home'))->assertOk();

    $posisi = array_map(
        fn (string $name): int => strpos($response->getContent(), '>'.$name.'<'),
        $urutan,
    );

    // Semua kategori harus tampil dan urutannya menaik dari kiri ke kanan.
    foreach ($posisi as $index => $pos) {
        expect($pos)->not->toBeFalse("Kategori {$urutan[$index]} tidak tampil di home.");
    }

    $sorted = $posisi;
    sort($sorted);

    expect($posisi)->toBe($sorted);
});
