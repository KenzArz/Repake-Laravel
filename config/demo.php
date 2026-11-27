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
    | Tautan Instance Demo
    |--------------------------------------------------------------------------
    |
    | Ditampilkan di modal login instance produksi sebagai pintu masuk juri.
    |
    */

    'url' => env('DEMO_URL', 'https://demo.repake.dpdns.org'),

];
