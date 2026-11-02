<?php

test('user can view profile catalog page', function () {
    $response = $this->get(route('profile.catalog'));

    $response->assertStatus(200);
    $response->assertSee('Midou_kun');
    $response->assertSee('Semua Produk');
});

test('user can view profile edit form', function () {
    $response = $this->get(route('profile.edit'));

    $response->assertStatus(200);
    $response->assertSee('Edit Profil');
    $response->assertSee('Nama Lengkap / Username');
    $response->assertSee('Nomor WhatsApp / Telepon');
    $response->assertSee('Lokasi / Asal Kampus / Domisili');
});

test('user can update profile information', function () {
    $response = $this->put(route('profile.update'), [
        'name' => 'Midou_Updated',
        'phone' => '0899-9999-9999',
        'kota' => 'Bandung',
        'bio' => 'Toko perlengkapan mechanical keyboard terlengkap.',
    ]);

    $response->assertRedirect(route('profile.catalog'));

    $profile = session('user_profile');
    expect($profile['name'])->toBe('Midou_Updated');
    expect($profile['kota'])->toBe('Bandung');
});

test('user can switch between dijual and favorit tabs in profile catalog', function () {
    $response = $this->get(route('profile.catalog', ['tab' => 'favorit']));

    $response->assertStatus(200);
    $response->assertSee('Favorit');
});
