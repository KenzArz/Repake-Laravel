<?php

use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->profileUser = User::factory()->create([
        'name' => 'Midou Wijaya',
        'username' => 'Midou_kun',
        'kota' => 'Bekasi',
    ]);

    $this->actingAs($this->profileUser);
});

test('user can view profile catalog page', function () {
    $response = $this->get(route('profile.catalog'));

    $response->assertStatus(200);
    $response->assertSee('Midou_kun');
    $response->assertSee('Semua Produk');
});

test('profile catalog renders product cards for owned products', function () {
    Product::factory()->create([
        'user_id' => $this->profileUser->id,
        'judul' => 'Keyboard mekanik custom',
    ]);

    $response = $this->get(route('profile.catalog'));

    $response->assertStatus(200);
    $response->assertSee('Keyboard mekanik custom');
});

test('user can view profile edit form', function () {
    $response = $this->get(route('profile.edit'));

    $response->assertStatus(200);
    $response->assertSee('Pengaturan Profil');
    $response->assertSee('Nama Lengkap');
    $response->assertSee('Username (Handle Toko)');
    $response->assertSee('Nomor WhatsApp / Telepon');
    $response->assertSee('Lokasi / Kampus / Domisili');
});

test('user can update profile information', function () {
    $response = $this->put(route('profile.update'), [
        'name' => 'Midou Updated',
        'username' => 'midou_super',
        'phone' => '0899-9999-9999',
        'kota' => 'Bandung',
        'bio' => 'Toko perlengkapan mechanical keyboard terlengkap.',
    ]);

    $response->assertRedirect(route('profile.catalog'));

    $user = $this->profileUser->refresh();
    expect($user->name)->toBe('Midou Updated')
        ->and($user->username)->toBe('midou_super')
        ->and($user->kota)->toBe('Bandung')
        ->and($user->phone)->toBe('0899-9999-9999');

    expect(session('user_profile')['name'])->toBe('Midou Updated');
});

test('updated profile is still shown after logging in again', function () {
    $this->put(route('profile.update'), [
        'name' => 'Mahasiswa Baru Unsika',
        'username' => 'mhs_unsika',
        'kota' => 'Telukjambe Timur',
        'bio' => 'Mahasiswa Teknik Informatika UNSIKA.',
    ])->assertRedirect(route('profile.catalog'));

    $this->post(route('logout'));

    $this->actingAs($this->profileUser->refresh());

    $response = $this->get(route('profile.edit'));
    $response->assertStatus(200);
    $response->assertSee('Mahasiswa Baru Unsika');
    $response->assertSee('mhs_unsika');
});

test('username must be unique', function () {
    User::factory()->create(['username' => 'sudah_dipakai']);

    $this->put(route('profile.update'), [
        'name' => 'Midou Updated',
        'username' => 'sudah_dipakai',
    ])->assertSessionHasErrors('username');
});

test('user can upload avatar and remove avatar', function () {
    Storage::fake('public');

    $file = UploadedFile::fake()->image('avatar.jpg', 200, 200);

    $response = $this->put(route('profile.update'), [
        'name' => 'Midou With Avatar',
        'avatar' => $file,
    ]);

    $response->assertRedirect(route('profile.catalog'));

    $user = $this->profileUser->refresh();
    expect($user->avatar)->not->toBeNull();
    expect(Storage::disk('public')->exists($user->avatar))->toBeTrue();

    // Hapus avatar via remove_avatar = 1
    $responseRemove = $this->put(route('profile.update'), [
        'name' => 'Midou Without Avatar',
        'remove_avatar' => '1',
    ]);

    $responseRemove->assertRedirect(route('profile.catalog'));

    $user->refresh();
    expect($user->avatar)->toBeNull();
});

test('user can switch between dijual and favorit tabs in profile catalog', function () {
    $response = $this->get(route('profile.catalog', ['tab' => 'favorit']));

    $response->assertStatus(200);
    $response->assertSee('Favorit');
});

test('user can view ulasan tab with buyer reviews from the database', function () {
    $reviewer = User::factory()->create(['name' => 'Aleww_kun']);
    $product = Product::factory()->create([
        'user_id' => $this->profileUser->id,
        'harga' => 100000,
        'judul' => 'Figur anime lucu',
    ]);
    $order = Order::factory()->selesai()->create([
        'seller_id' => $this->profileUser->id,
        'buyer_id' => $reviewer->id,
        'product_id' => $product->id,
    ]);
    Review::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'reviewer_id' => $reviewer->id,
        'seller_id' => $this->profileUser->id,
        'rating' => 5,
        'komentar' => 'Penjual fast respon, bass nya mendut mendut, puas dipake seharian',
    ]);

    $response = $this->get(route('profile.catalog', ['tab' => 'ulasan']));

    $response->assertStatus(200);
    $response->assertSee('Semua Ulasan');
    $response->assertSee('Aleww_kun');
    $response->assertSee('Penjual fast respon, bass nya mendut mendut, puas dipake seharian');
    $response->assertSee('Figur anime lucu');
    $response->assertSee('Rp 100.000');
});

test('default silhouette avatar is displayed across profile and cards when avatar is null', function () {
    $response = $this->get(route('profile.catalog'));

    $response->assertStatus(200);
    $response->assertSee('images/default-avatar.png');
});
