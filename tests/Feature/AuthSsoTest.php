<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('login modal is present on the page for guests', function () {
    $response = $this->get(route('home'));

    $response->assertStatus(200);
    $response->assertSee('Masuk ke RePake');
    $response->assertSee('Silahkan masuk menggunakan akun Google UNSIKA anda');
    $response->assertSee('Continue with Google');
});

test('login modal no longer contains manual email login', function () {
    $response = $this->get(route('home'));

    $response->assertStatus(200);
    $response->assertDontSee('custom-email-input', false);
    $response->assertDontSee('performSsoLogin', false);
    $response->assertDontSee('Ketik email @student.unsika.ac.id');
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

test('authenticated user navbar shows avatar dropdown', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->get(route('home'));
    $response->assertStatus(200);
    $response->assertSee('user-menu-wrapper');
    $response->assertSee('user-menu-toggle');
    $response->assertSee('user-menu-dropdown');
    $response->assertSee('Katalog Saya');
    $response->assertSee('Keluar (Logout)');
});
