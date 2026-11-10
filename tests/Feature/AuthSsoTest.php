<?php

test('login modal is present on the page for guests', function () {
    $response = $this->get(route('home'));

    $response->assertStatus(200);
    $response->assertSee('Masuk ke RePake');
    $response->assertSee('Silahkan masuk menggunakan akun Google UNSIKA anda');
    $response->assertSee('Continue with Google');
});

test('user can login successfully with student unsika google account', function () {
    $response = $this->postJson(route('login.google'), [
        'email' => 'ale.awaludin@student.unsika.ac.id',
        'name' => 'Ale Awaludin',
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
    ]);

    $this->assertAuthenticated();
    expect(auth()->user()->email)->toBe('ale.awaludin@student.unsika.ac.id');
    expect(session('user_profile')['email'])->toBe('ale.awaludin@student.unsika.ac.id');
});

test('login is rejected if email is not from student unsika domain', function () {
    $response = $this->postJson(route('login.google'), [
        'email' => 'ale.awaludin@gmail.com',
        'name' => 'Ale Awaludin',
    ]);

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'message' => 'Akses ditolak: Hanya akun Google dengan domain @student.unsika.ac.id yang diizinkan masuk ke RePake.',
    ]);

    $this->assertGuest();
});

test('user can logout successfully', function () {
    $this->postJson(route('login.google'), [
        'email' => 'ale.awaludin@student.unsika.ac.id',
        'name' => 'Ale Awaludin',
    ]);

    $this->assertAuthenticated();

    $response = $this->post(route('logout'));
    $response->assertRedirect(route('home'));
    $this->assertGuest();
});

test('guest navbar shows masuk button, logo, and action elements', function () {
    $response = $this->get(route('home'));

    $response->assertStatus(200);
    $response->assertSee('images/repake-logo.png');
    $response->assertSee('RePake');
    $response->assertSee('data-open-login', false);
    $response->assertSee('Masuk');
    $response->assertSee('+Jual');
    $response->assertDontSee('user-menu-wrapper');
});

test('authenticated user navbar shows avatar dropdown and logout option without username text button', function () {
    $this->postJson(route('login.google'), [
        'email' => 'ale.awaludin@student.unsika.ac.id',
        'name' => 'Ale Awaludin',
    ]);

    $this->assertAuthenticated();

    $response = $this->get(route('home'));
    $response->assertStatus(200);
    $response->assertSee('user-menu-wrapper');
    $response->assertSee('user-menu-toggle');
    $response->assertSee('user-menu-dropdown');
    $response->assertSee('Katalog Saya');
    $response->assertSee('Keluar (Logout)');
});
