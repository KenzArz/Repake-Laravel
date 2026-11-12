<?php

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seller = User::factory()->create(['username' => 'penjual_uuid']);
    $this->other = User::factory()->create();
    $this->product = Product::factory()->create([
        'user_id' => $this->seller->id,
        'judul' => 'Keyboard Mekanik RGB',
    ]);
});

test('product detail uses a uuid query parameter and renders the canonical url', function () {
    $url = $this->product->detailUrl();

    expect($url)->toContain('/produk/')
        ->toContain('?id='.$this->product->id);

    $this->get($url)
        ->assertOk()
        ->assertSee('Keyboard Mekanik RGB')
        ->assertSee('rel="canonical"', false)
        ->assertSee($url, false);
});

test('two products with identical titles keep distinct urls', function () {
    $twin = Product::factory()->create([
        'user_id' => $this->seller->id,
        'judul' => 'Keyboard Mekanik RGB',
    ]);

    expect($this->product->detailUrl())->not->toBe($twin->detailUrl());

    $this->get($this->product->detailUrl())->assertOk();
    $this->get($twin->detailUrl())->assertOk();
});

test('a stale slug with the correct uuid redirects permanently to the canonical url', function () {
    $this->get('/produk/slug-yang-sudah-usang?id='.$this->product->id)
        ->assertStatus(301)
        ->assertRedirect($this->product->detailUrl());
});

test('missing uuid redirects permanently to the canonical url', function () {
    $this->get('/produk/'.$this->product->slug)
        ->assertStatus(301)
        ->assertRedirect($this->product->detailUrl());
});

test('the legacy products path redirects permanently to the canonical url', function () {
    $this->get('/products/'.$this->product->slug)
        ->assertStatus(301)
        ->assertRedirect($this->product->detailUrl());
});

test('invalid and numeric identifiers no longer resolve', function () {
    $this->get('/produk/'.$this->product->slug.'?id=bukan-uuid')->assertNotFound();
    $this->get('/produk/'.$this->product->slug.'?id='.$this->other->id)->assertNotFound();
    $this->get('/produk/1')->assertNotFound();
    $this->get('/products/1')->assertNotFound();
});

test('draft products are hidden from other users but visible to the owner', function () {
    $draft = Product::factory()->draft()->create(['user_id' => $this->seller->id]);

    $this->get($draft->detailUrl())->assertNotFound();

    $this->actingAs($this->other)->get($draft->detailUrl())->assertNotFound();

    $this->actingAs($this->seller)
        ->get($draft->detailUrl())
        ->assertOk()
        ->assertSee('Draft');
});

test('a non owner cannot edit update or delete another product', function () {
    $this->actingAs($this->other)
        ->get(route('products.edit', $this->product))
        ->assertForbidden();

    $this->actingAs($this->other)
        ->put(route('products.update', $this->product), [])
        ->assertForbidden();

    $this->actingAs($this->other)
        ->delete(route('products.destroy', $this->product))
        ->assertForbidden();

    expect(Product::find($this->product->id))->not->toBeNull();
});

test('seller profile canonical url includes the uuid and stale usernames redirect', function () {
    $url = $this->seller->profileUrl();

    expect($url)->toContain('/penjual/penjual_uuid')
        ->toContain('?id='.$this->seller->id);

    $this->get($url)->assertOk()->assertSee('penjual_uuid');

    $this->get('/penjual/username-lama?id='.$this->seller->id)
        ->assertStatus(301)
        ->assertRedirect($url);

    $this->get('/penjual/penjual_uuid')
        ->assertStatus(301)
        ->assertRedirect($url);
});

test('seller without a username is addressed by uuid', function () {
    $seller = User::factory()->create(['username' => null]);

    expect($seller->profileUrl())->toBe(route('sellers.show', $seller->id));

    $this->get($seller->profileUrl())->assertOk()->assertSee($seller->name);
});

test('public seller profile tab links keep exactly one query separator', function () {
    // Profil dengan username: $profileUrl sudah membawa ?id={uuid}.
    $profileUrl = $this->seller->profileUrl();

    $this->get($profileUrl)
        ->assertOk()
        ->assertSee($profileUrl.'&tab=ulasan')
        ->assertDontSee($profileUrl.'?tab=ulasan');

    $this->get($profileUrl.'&tab=ulasan')
        ->assertOk()
        ->assertSee('Ulasan');

    // Profil tanpa username: tidak ada query string, jadi wajib memakai `?`.
    $noUsername = User::factory()->create(['username' => null]);
    $plainUrl = $noUsername->profileUrl();

    $this->get($plainUrl)
        ->assertOk()
        ->assertSee($plainUrl.'?tab=ulasan');

    $this->get($plainUrl.'?tab=ulasan')->assertOk();
});

test('favorites only accept a product uuid', function () {
    $this->actingAs($this->other)
        ->postJson(route('favorites.toggle', $this->product->id))
        ->assertOk()
        ->assertJsonPath('is_favorite', true);

    $this->actingAs($this->other)
        ->postJson(route('favorites.toggle', '1'))
        ->assertNotFound();
});

test('reports only accept a product uuid', function () {
    $this->actingAs($this->other)
        ->post(route('products.report', '1'), [
            'alasan' => 'penipuan',
            'keterangan' => 'Barang ini terindikasi penipuan.',
        ])
        ->assertNotFound();
});

test('chat can only be started from a product uuid', function () {
    $this->actingAs($this->other)
        ->postJson(route('chat.store'), ['product_id' => $this->product->id])
        ->assertRedirect();

    $this->actingAs($this->other)
        ->postJson(route('chat.store'), ['product_id' => '1'])
        ->assertStatus(422);
});
