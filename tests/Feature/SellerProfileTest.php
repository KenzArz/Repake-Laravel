<?php

use App\Models\Conversation;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seller = User::factory()->create([
        'name' => 'Budi Penjual',
        'username' => 'budi_jual',
        'kota' => 'Karawang',
        'bio' => 'Jual barang bekas layak pakai.',
    ]);

    $this->viewer = User::factory()->create(['username' => 'pembeli_satu']);
});

test('guest can open a seller profile from a product page', function () {
    Product::factory()->create([
        'user_id' => $this->seller->id,
        'judul' => 'Keyboard mekanik budi',
    ]);

    $response = $this->get($this->seller->profileUrl());

    $response->assertOk();
    $response->assertSee('Budi Penjual');
    $response->assertSee('budi_jual');
    $response->assertSee('Jual barang bekas layak pakai.');
    $response->assertSee('Karawang');
    $response->assertSee('Keyboard mekanik budi');
    $response->assertSee('Semua Produk');
});

test('public seller profile only has produk and ulasan tabs', function () {
    Product::factory()->create(['user_id' => $this->seller->id]);

    $response = $this->actingAs($this->viewer)->get($this->seller->profileUrl());

    $response->assertOk();
    $response->assertSee('tab=ulasan', false);
    $response->assertDontSee('tab=favorit', false);
    $response->assertSee('seller-chat-open', false);
});

test('own profile still shows the favorite tab', function () {
    $response = $this->actingAs($this->seller)->get(route('profile.catalog'));

    $response->assertOk();
    $response->assertSee('tab=favorit', false);
});

test('public seller profile only shows products with status dijual', function () {
    Product::factory()->create([
        'user_id' => $this->seller->id,
        'judul' => 'Produk Tampil',
    ]);
    Product::factory()->draft()->create([
        'user_id' => $this->seller->id,
        'judul' => 'Produk Draft Rahasia',
    ]);
    Product::factory()->terjual()->create([
        'user_id' => $this->seller->id,
        'judul' => 'Produk Sudah Terjual',
    ]);
    Product::factory()->create([
        'user_id' => $this->seller->id,
        'judul' => 'Produk Ditangguhkan',
        'status' => Product::STATUS_DITANGGUIHKAN,
    ]);

    $response = $this->get($this->seller->profileUrl());

    $response->assertOk();
    $response->assertSee('Produk Tampil');
    $response->assertDontSee('Produk Draft Rahasia');
    $response->assertDontSee('Produk Sudah Terjual');
    $response->assertDontSee('Produk Ditangguhkan');
});

test('banned seller profile is not accessible', function () {
    $this->seller->forceFill(['is_banned' => true])->save();

    $this->get($this->seller->profileUrl())->assertNotFound();
});

test('opening own seller profile redirects to the private profile', function () {
    $this->actingAs($this->seller)
        ->get($this->seller->profileUrl())
        ->assertRedirect(route('profile.catalog'));
});

test('unknown seller is not found and numeric ids no longer resolve', function () {
    $this->get(route('sellers.show', 'username-tidak-ada'))->assertNotFound();

    $this->get(route('sellers.show', '11'))->assertNotFound();

    $this->get($this->seller->profileUrl())
        ->assertOk()
        ->assertSee('Budi Penjual');
});

test('seller profile shows reviews written by buyers', function () {
    $product = Product::factory()->create([
        'user_id' => $this->seller->id,
        'judul' => 'Figur anime lucu',
        'harga' => 100000,
    ]);

    $order = Order::factory()->selesai()->create([
        'seller_id' => $this->seller->id,
        'buyer_id' => $this->viewer->id,
        'product_id' => $product->id,
    ]);

    Review::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'reviewer_id' => $this->viewer->id,
        'seller_id' => $this->seller->id,
        'rating' => 5,
        'komentar' => 'Penjual fast respon dan barang sesuai deskripsi',
    ]);

    $response = $this->get($this->seller->profileUrl().'&tab=ulasan');

    $response->assertOk();
    $response->assertSee('Semua Ulasan');
    $response->assertSee('Penjual fast respon dan barang sesuai deskripsi');
    $response->assertSee('Figur anime lucu');
});

test('product page links the seller name to their profile', function () {
    $product = Product::factory()->create(['user_id' => $this->seller->id]);

    $response = $this->get($product->detailUrl());

    $response->assertOk();
    $response->assertSee($this->seller->profileUrl(), false);
});

test('chat header links to the counterpart profile', function () {
    $product = Product::factory()->create(['user_id' => $this->seller->id]);
    $conversation = Conversation::factory()->create([
        'buyer_id' => $this->viewer->id,
        'seller_id' => $this->seller->id,
        'product_id' => $product->id,
    ]);

    $this->actingAs($this->viewer)
        ->get(route('chat.show', $conversation))
        ->assertOk()
        ->assertSee($this->seller->profileUrl(), false);
});

test('chat cannot be started for suspended products or banned sellers', function () {
    $suspended = Product::factory()->create([
        'user_id' => $this->seller->id,
        'status' => Product::STATUS_DITANGGUIHKAN,
    ]);

    $this->actingAs($this->viewer)
        ->post(route('chat.store'), ['product_id' => $suspended->id])
        ->assertNotFound();

    $bannedSeller = User::factory()->banned()->create();
    $product = Product::factory()->create(['user_id' => $bannedSeller->id]);

    $this->actingAs($this->viewer)
        ->post(route('chat.store'), ['product_id' => $product->id])
        ->assertNotFound();
});
