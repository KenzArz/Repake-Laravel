<?php

namespace Database\Seeders;

use App\Models\Banner;
use Illuminate\Database\Seeder;

class BannerSeeder extends Seeder
{
    public function run(): void
    {
        $banners = [
            [
                'sort_order' => 1,
                'title' => 'Preloved Kampus Berkualitas & Hemat',
                'image_path' => 'images/repake-banner-notext.webp',
                'alt_text' => 'Temukan barang bekas layak pakai di sekitar kampus dengan harga ramah mahasiswa.',
                'is_active' => true,
            ],
            [
                'sort_order' => 2,
                'title' => 'COD Praktis di Titik Temu Favoritmu',
                'image_path' => 'images/repake-banner-notext.webp',
                'alt_text' => 'Pilih lokasi terdekat seperti Gerbang Kampus UNSIKA, Galuh Mas, atau Telukjambe.',
                'is_active' => true,
            ],
            [
                'sort_order' => 3,
                'title' => 'Barang Nggak Terpakai? Ubah Jadi Cuan!',
                'image_path' => 'images/repake-banner-notext.webp',
                'alt_text' => 'Pasang iklan gratis dalam hitungan menit dan langsung terhubung dengan pembeli.',
                'is_active' => true,
            ],
        ];

        foreach ($banners as $banner) {
            Banner::updateOrCreate(
                ['sort_order' => $banner['sort_order']],
                $banner
            );
        }
    }
}
