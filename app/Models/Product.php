<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Product extends Model
{
    /**
     * Data sampel produk yang konsisten di seluruh aplikasi.
     *
     * @return Collection<int, object>
     */
    public static function dummyCatalog(): Collection
    {
        if (! session()->has('favorites')) {
            // Default sampel favorit awal (produk 2 dan 4)
            session()->put('favorites', [2, 4]);
        }

        $favorites = session()->get('favorites', []);

        $samples = [
            [
                'id' => 1,
                'penjual' => 'TechStore_ID',
                'kota' => 'Jakarta Pusat',
                'created_at' => Carbon::now()->subHours(2),
                'status' => 'Dijual',
                'judul' => 'Keyboard Mechanical RGB Gaming Bekas Mulus',
                'harga' => 450000,
                'kondisi' => 'Seperti baru',
                'gambar' => 'https://images.unsplash.com/photo-1595225476474-87563907a212?w=600&auto=format&fit=crop&q=80',
            ],
            [
                'id' => 2,
                'penjual' => 'Midou_kun',
                'kota' => 'Bekasi',
                'created_at' => Carbon::now()->subHours(5),
                'status' => 'Dijual',
                'judul' => 'Sepatu Sneakers Nike Air Force 1 Original Second',
                'harga' => 850000,
                'kondisi' => 'Dirawat dengan baik',
                'gambar' => 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=600&auto=format&fit=crop&q=80',
            ],
            [
                'id' => 3,
                'penjual' => 'VintageKuy',
                'kota' => 'Bandung',
                'created_at' => Carbon::now()->subHours(8),
                'status' => 'Dijual',
                'judul' => 'Jaket Denim Vintage 90s Unisex Thrift Premium',
                'harga' => 180000,
                'kondisi' => 'Bekas pakai',
                'gambar' => 'https://images.unsplash.com/photo-1551028719-00167b16eac5?w=600&auto=format&fit=crop&q=80',
            ],
            [
                'id' => 4,
                'penjual' => 'CameraBekas_Pro',
                'kota' => 'Tangerang',
                'created_at' => Carbon::now()->subHours(12),
                'status' => 'Dijual',
                'judul' => 'Kamera DSLR Canon EOS 70D Fullset Mulus Siap Pakai',
                'harga' => 4500000,
                'kondisi' => 'Seperti baru',
                'gambar' => 'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?w=600&auto=format&fit=crop&q=80',
            ],
            [
                'id' => 5,
                'penjual' => 'TechStore_ID',
                'kota' => 'Jakarta Pusat',
                'created_at' => Carbon::now()->subDays(1),
                'status' => 'Dijual',
                'judul' => 'Samsung Galaxy Earbuds Live True Wireless ANC',
                'harga' => 350000,
                'kondisi' => 'Dirawat dengan baik',
                'gambar' => 'https://images.unsplash.com/photo-1590658268037-6bf12165a8df?w=600&auto=format&fit=crop&q=80',
            ],
            [
                'id' => 6,
                'penjual' => 'UrbanOutfit',
                'kota' => 'Jakarta Selatan',
                'created_at' => Carbon::now()->subDays(1),
                'status' => 'Dijual',
                'judul' => 'Kemeja Flannel Pria Motif Kotak Lengan Panjang',
                'harga' => 170000,
                'kondisi' => 'Dirawat dengan baik',
                'gambar' => 'https://images.unsplash.com/photo-1589310243389-96a5483213a8?w=600&auto=format&fit=crop&q=80',
            ],
            [
                'id' => 7,
                'penjual' => 'GamerCorner',
                'kota' => 'Surabaya',
                'created_at' => Carbon::now()->subDays(2),
                'status' => 'Dijual',
                'judul' => 'Mouse Gaming Wireless Logitech G Pro X Superlight',
                'harga' => 950000,
                'kondisi' => 'Seperti baru',
                'gambar' => 'https://images.unsplash.com/photo-1615663245857-ac93bb7c39e7?w=600&auto=format&fit=crop&q=80',
            ],
            [
                'id' => 8,
                'penjual' => 'BookNook',
                'kota' => 'Yogyakarta',
                'created_at' => Carbon::now()->subDays(3),
                'status' => 'Dijual',
                'judul' => 'Paket Novel Harry Potter 1-7 Bahasa Indonesia Segel',
                'harga' => 550000,
                'kondisi' => 'Dirawat dengan baik',
                'gambar' => 'https://images.unsplash.com/photo-1512820790803-83ca734da794?w=600&auto=format&fit=crop&q=80',
            ],
        ];

        return collect($samples)->map(function (array $item) use ($favorites): object {
            $item['is_favorite'] = in_array($item['id'], $favorites, true);

            return (object) $item;
        });
    }

    public static function findDummy(int|string $id): ?object
    {
        return static::dummyCatalog()->firstWhere('id', (int) $id);
    }
}
