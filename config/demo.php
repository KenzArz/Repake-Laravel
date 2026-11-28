<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mode Demo Penilaian
    |--------------------------------------------------------------------------
    |
    | Saat aktif, instance ini membuka jalur login khusus juri tanpa akun
    | kampus. Hanya tiga persona yang tersedia — tidak ada "login as anyone".
    | Nilai ini WAJIB false di instance produksi.
    |
    */

    'enabled' => (bool) env('DEMO_MODE', false),

    'personas' => [
        'pembeli' => env('DEMO_BUYER_EMAIL', 'juri.pembeli@demo.local'),
        'penjual' => env('DEMO_SELLER_EMAIL', 'juri.penjual@demo.local'),
        'admin' => env('DEMO_ADMIN_EMAIL', 'juri.admin@demo.local'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Nama Tampilan Persona
    |--------------------------------------------------------------------------
    |
    | Email persona sengaja tetap memakai domain demo, tetapi nama yang
    | terlihat di navbar, panel, dan percakapan memakai nama orang biasa agar
    | suasana demo terasa seperti aplikasi sungguhan. Nilai ini juga dipakai
    | DemoSeeder supaya nama di database dan panel tidak pernah berbeda.
    |
    */

    'names' => [
        'pembeli' => 'Alya Ramadhani',
        'penjual' => 'Rizky Pratama',
        'admin' => 'Admin Repake Demo',
    ],

    /*
    |--------------------------------------------------------------------------
    | Halaman Aman Saat Berganti Persona
    |--------------------------------------------------------------------------
    |
    | Panel demo meneruskan URI halaman aktif sebagai tujuan setelah login.
    | Hanya path yang cocok dengan pola di bawah yang dipertahankan untuk
    | persona pembeli/penjual; halaman lain jatuh ke beranda supaya juri
    | tidak menabrak 403 (mis. /admin/* atau /products/{id}/edit).
    | Pola memakai wildcard `*` dari Str::is().
    |
    */

    'safe_paths' => [
        '/',
        '/products',
        '/products/create',
        '/produk/*',
        '/penjual/*',
        '/chat',
        '/chat/*',
        '/orders',
        '/profile',
        '/profile/*',
        '/favorites',
        '/notifications',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tautan Instance Demo
    |--------------------------------------------------------------------------
    |
    | Ditampilkan di modal login instance produksi sebagai pintu masuk juri.
    |
    */

    'url' => env('DEMO_URL', 'https://demo.repake.dpdns.org'),

    /*
    |--------------------------------------------------------------------------
    | Tautan Instance Produksi
    |--------------------------------------------------------------------------
    |
    | Dipakai tombol Google di modal login instance demo. Login Google hanya
    | berjalan di produksi, jadi tombol tersebut mengarah ke alamat ini.
    |
    */

    'production_url' => env('DEMO_PRODUCTION_URL', 'https://repake.dpdns.org'),

];
