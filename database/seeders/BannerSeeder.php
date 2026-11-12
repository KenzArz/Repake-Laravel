<?php

namespace Database\Seeders;

use App\Models\Banner;
use Illuminate\Database\Seeder;

class BannerSeeder extends Seeder
{
    /**
     * Tiga banner demo agar beranda dan syarat minimal 3 banner terpenuhi.
     */
    public function run(): void
    {
        $banners = [
            [
                'title' => 'Welcome ke RePake',
                'image_path' => 'products/keyboard-mechanical.jpg',
                'alt_text' => 'Promo selamat datang di RePake',
            ],
            [
                'title' => 'Best Deal Minggu Ini',
                'image_path' => 'products/kamera-canon-70d.jpg',
                'alt_text' => 'Promo barang second pilihan',
            ],
            [
                'title' => 'New Arrival Preloved',
                'image_path' => 'products/nike-af1.jpg',
                'alt_text' => 'Koleksi preloved terbaru',
            ],
        ];

        foreach ($banners as $index => $banner) {
            Banner::updateOrCreate(
                ['title' => $banner['title']],
                [
                    'image_path' => $banner['image_path'],
                    'alt_text' => $banner['alt_text'],
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ],
            );
        }
    }
}
