<?php

use Illuminate\Http\UploadedFile;

test('user can view profile catalog page', function () {
    $response = $this->get(route('profile.catalog'));

    $response->assertStatus(200);
    $response->assertSee('Midou_kun');
    $response->assertSee('Semua Produk');
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

    $profile = session('user_profile');
    expect($profile['name'])->toBe('Midou Updated');
    expect($profile['username'])->toBe('midou_super');
    expect($profile['kota'])->toBe('Bandung');
});

test('user can view edit form and update profile after logging in with sso', function () {
    // Login SSO terlebih dahulu
    $this->postJson(route('login.google'), [
        'email' => 'mahasiswa.baru@student.unsika.ac.id',
        'name' => 'Mahasiswa Baru',
    ]);

    // Buka form edit profil (memastikan tidak ada undefined key error)
    $response = $this->get(route('profile.edit'));
    $response->assertStatus(200);
    $response->assertSee('Mahasiswa Baru');

    // Update profil
    $updateResponse = $this->put(route('profile.update'), [
        'name' => 'Mahasiswa Baru Unsika',
        'username' => 'mhs_unsika',
        'phone' => '0812-9876-5432',
        'kota' => 'Telukjambe Timur',
        'bio' => 'Mahasiswa Teknik Informatika UNSIKA.',
    ]);

    $updateResponse->assertRedirect(route('profile.catalog'));
    expect(session('user_profile')['name'])->toBe('Mahasiswa Baru Unsika');
    expect(session('user_profile')['kota'])->toBe('Telukjambe Timur');
});

test('user can upload avatar and remove avatar', function () {
    $file = UploadedFile::fake()->image('avatar.jpg', 200, 200);

    $response = $this->put(route('profile.update'), [
        'name' => 'Midou With Avatar',
        'avatar' => $file,
    ]);

    $response->assertRedirect(route('profile.catalog'));
    $profile = session('user_profile');
    expect($profile['avatar'])->not->toBeNull();
    expect(file_exists(public_path($profile['avatar'])))->toBeTrue();

    // Hapus file buatan pengujian
    if (file_exists(public_path($profile['avatar']))) {
        unlink(public_path($profile['avatar']));
    }

    // Hapus avatar via remove_avatar = 1
    $responseRemove = $this->put(route('profile.update'), [
        'name' => 'Midou Without Avatar',
        'remove_avatar' => '1',
    ]);

    $responseRemove->assertRedirect(route('profile.catalog'));
    expect(session('user_profile')['avatar'])->toBeNull();
});

test('user can switch between dijual and favorit tabs in profile catalog', function () {
    $response = $this->get(route('profile.catalog', ['tab' => 'favorit']));

    $response->assertStatus(200);
    $response->assertSee('Favorit');
});

test('user can view ulasan tab with buyer reviews and sold products in catalog', function () {
    $response = $this->get(route('profile.catalog', ['tab' => 'ulasan']));

    $response->assertStatus(200);
    $response->assertSee('Semua Ulasan');
    $response->assertSee('Aleww_kun');
    $response->assertSee('Penjual fast respon, bass nya mendut mendut, puas dipake seharian');
    $response->assertSee('Figur anime lucu');
    $response->assertSee('Rp 100.000');
    $response->assertSee('images/default-avatar.png');
});

test('default silhouette avatar is displayed across profile and cards when avatar is null', function () {
    $response = $this->get(route('profile.catalog'));

    $response->assertStatus(200);
    $response->assertSee('images/default-avatar.png');
});
