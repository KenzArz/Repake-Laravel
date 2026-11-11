<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\CodArea;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = Category::all()->keyBy('name');
        $users = User::all()->keyBy('username');

        $products = [
            [
                'username' => 'techstore_id',
                'category_name' => 'Elektronik',
                'title' => 'Keyboard Mechanical RGB Gaming Bekas Mulus',
                'description' => "Kondisi 98% sangat mulus pemakaian pribadi baru 3 bulan.\n- Switch Outemu Blue responsif dan renyah\n- RGB custom lighting dengan 18 mode efek\n- Lengkap kabel braided USB Type-C & keycap puller\n- Tidak ada minus fungsi, tombol semua normal dan responsif.",
                'price' => 450000,
                'condition' => 'bekas_baik',
                'status' => 'tersedia',
                'image' => 'https://images.unsplash.com/photo-1595225476474-87563907a212?w=600&auto=format&fit=crop&q=80',
                'cod_city' => 'Jakarta Pusat',
            ],
            [
                'username' => 'midou_kun',
                'category_name' => 'Pakaian',
                'title' => 'Sepatu Sneakers Nike Air Force 1 Original Second',
                'description' => "Sepatu Nike Air Force 1 100% Original pembelian di store resmi.\n- Ukuran: 42 (26.5 cm)\n- Warna: Wheat/Flax Coklat Premium\n- Tapak masih tebal, insole original, minus pemakaian wajar\n- Dilengkapi box original pengganti yang rapi.",
                'price' => 850000,
                'condition' => 'bekas_baik',
                'status' => 'tersedia',
                'image' => 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=600&auto=format&fit=crop&q=80',
                'cod_city' => 'Bekasi',
            ],
            [
                'username' => 'vintagekuy',
                'category_name' => 'Pakaian',
                'title' => 'Jaket Denim Vintage 90s Unisex Thrift Premium',
                'description' => "Jaket denim vintage look oversize style 90-an.\n- Bahan denim tebal berkualitas tidak kaku\n- Warna hitam washed original vintage\n- Size L (P: 68 cm, L: 58 cm), cocok untuk unisex pria/wanita\n- Sudah di-laundry wangi dan siap langsung pakai.",
                'price' => 180000,
                'condition' => 'bekas_layak',
                'status' => 'tersedia',
                'image' => 'https://images.unsplash.com/photo-1551028719-00167b16eac5?w=600&auto=format&fit=crop&q=80',
                'cod_city' => 'Bandung',
            ],
            [
                'username' => 'camerabekas_pro',
                'category_name' => 'Elektronik',
                'title' => 'Kamera DSLR Canon EOS 70D Fullset Mulus Siap Pakai',
                'description' => "Canon EOS 70D kondisi mulus terawat 95%.\n- Shutter count rendah (< 12.xxx)\n- Layar flip touchscreen normal, sensor bersih dari jamur\n- Kelengkapan: Unit kamera, lensa kit 18-55mm IS STM, baterai ori, charger, strap, dan tas kamera.",
                'price' => 4500000,
                'condition' => 'bekas_baik',
                'status' => 'tersedia',
                'image' => 'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?w=600&auto=format&fit=crop&q=80',
                'cod_city' => 'Tangerang',
            ],
            [
                'username' => 'techstore_id',
                'category_name' => 'Elektronik',
                'title' => 'Samsung Galaxy Earbuds Live True Wireless ANC',
                'description' => "Samsung Galaxy Buds Live warna Mystic Bronze.\n- Suara jernih dengan bass mantap hasil tuning AKG\n- Active Noise Cancelling berfungsi normal\n- Baterai awet tahan seharian bersama case charging\n- Unit TWS, case, dan kabel charger Type-C.",
                'price' => 350000,
                'condition' => 'bekas_baik',
                'status' => 'tersedia',
                'image' => 'https://images.unsplash.com/photo-1590658268037-6bf12165a8df?w=600&auto=format&fit=crop&q=80',
                'cod_city' => 'Jakarta Pusat',
            ],
            [
                'username' => 'urbanoutfit',
                'category_name' => 'Pakaian',
                'title' => 'Kemeja Flannel Pria Motif Kotak Lengan Panjang',
                'description' => "Kemeja flanel premium bahan katun tebal lembut tidak panas.\n- Motif kotak-kotak modern warna navy-maroon\n- Size XL reguler fit (Lebar dada 56 cm, Panjang 74 cm)\n- Kancing lengkap dan jahitan rapi tanpa robek.",
                'price' => 170000,
                'condition' => 'bekas_baik',
                'status' => 'tersedia',
                'image' => 'https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?w=600&auto=format&fit=crop&q=80',
                'cod_city' => 'Jakarta Selatan',
            ],
            [
                'username' => 'midou_kun',
                'category_name' => 'Elektronik',
                'title' => 'Monitor Gaming LG 24 Inch 144Hz IPS Panel Fullset',
                'description' => "Monitor gaming LG UltraGear 24GN60R.\n- Refresh rate 144Hz 1ms response time IPS Panel\n- No dead pixel, warna akurat dan sangat smooth untuk game FPS\n- Lengkap kabel HDMI, DisplayPort, adapter original, dan kardus bawaan.",
                'price' => 1650000,
                'condition' => 'bekas_baik',
                'status' => 'tersedia',
                'image' => 'https://images.unsplash.com/photo-1527443224154-c4a3942d3acf?w=600&auto=format&fit=crop&q=80',
                'cod_city' => 'Bekasi',
            ],
            [
                'username' => 'vintagekuy',
                'category_name' => 'Pakaian',
                'title' => 'Celana Chino Slim Fit Beige Ukuran 32 Bahan Melar',
                'description' => "Celana Chino warna beige khaki.\n- Bahan katun twill stretch melar nyaman dipakai harian/kuliah\n- Size 32 (Lingkar pinggang 84 cm)\n- Warna masih pekat, retsleting YKK lancar tidak macet.",
                'price' => 125000,
                'condition' => 'bekas_baik',
                'status' => 'tersedia',
                'image' => 'https://images.unsplash.com/photo-1473966968600-fa801b869a1a?w=600&auto=format&fit=crop&q=80',
                'cod_city' => 'Bandung',
            ],
            [
                'username' => 'booklover_bdg',
                'category_name' => 'Buku',
                'title' => 'Buku Atomic Habits James Clear Terjemahan Gramedia',
                'description' => "Buku Atomic Habits Bahasa Indonesia original Gramedia.\n- Sudah disampul mika bening rapi\n- Kertas bersih tanpa coretan stabilo atau lipatan halaman\n- Sangat cocok untuk self-improvement dan bangun kebiasaan positif.",
                'price' => 65000,
                'condition' => 'bekas_baik',
                'status' => 'tersedia',
                'image' => 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=600&auto=format&fit=crop&q=80',
                'cod_city' => 'Bandung',
            ],
            [
                'username' => 'furniturekuy',
                'category_name' => 'Perabot',
                'title' => 'Meja Kerja Belajar Minimalis Kayu Jati Belanda Kokoh',
                'description' => "Meja kerja / meja belajar minimalis aesthetic.\n- Bahan solid wood kayu jati belanda serat natural vernis halus\n- Dimensi: Panjang 100 cm, Lebar 50 cm, Tinggi 75 cm\n- Kaki besi hollow kokoh anti karat dan anti goyang\n- Bisa dibongkar pasang mudah dibawa motor/mobil.",
                'price' => 320000,
                'condition' => 'bekas_baik',
                'status' => 'tersedia',
                'image' => 'https://images.unsplash.com/photo-1518455027359-f3f8164ba6bd?w=600&auto=format&fit=crop&q=80',
                'cod_city' => 'Karawang',
            ],
            [
                'username' => 'gadgetsecond_bdg',
                'category_name' => 'Elektronik',
                'title' => 'Smartwatch Amazfit GTS 2 Mini AMOLED Layar Mulus',
                'description' => "Amazfit GTS 2 Mini warna Midnight Black.\n- Layar Always-On AMOLED jernih dan tajam tanpa baret\n- Sensor SpO2, Heart Rate, Sleep Tracker, GPS akurat\n- Baterai awet tahan 7-10 hari pemakaian normal\n- Lengkap strap cadangan, kabel charger magnetik, dan box.",
                'price' => 490000,
                'condition' => 'bekas_baik',
                'status' => 'tersedia',
                'image' => 'https://images.unsplash.com/photo-1508685096489-7aacd43bd3b1?w=600&auto=format&fit=crop&q=80',
                'cod_city' => 'Bandung',
            ],
            [
                'username' => 'melodymusic',
                'category_name' => 'Hobi',
                'title' => 'Gitar Akustik Yamaha F310 Original Softcase Busa Tebal',
                'description' => "Yamaha F310 Natural original bukan custom abal-abal.\n- Suara nyaring dan sustain panjang khas Yamaha\n- Neck lurus, action ceper tidak sakit di jari, fretboard bersih\n- Senar D'Addario baru diganti\n- Bonus: Softcase busa tebal, capo, dan pick gitar.",
                'price' => 880000,
                'condition' => 'bekas_baik',
                'status' => 'tersedia',
                'image' => 'https://images.unsplash.com/photo-1510915361894-db8b60106cb1?w=600&auto=format&fit=crop&q=80',
                'cod_city' => 'Jakarta Barat',
            ],
        ];

        foreach ($products as $index => $item) {
            $user = $users->get($item['username']);
            $category = $categories->get($item['category_name']);

            if (! $user || ! $category) {
                continue;
            }

            $product = Product::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'title' => $item['title'],
                ],
                [
                    'category_id' => $category->id,
                    'description' => $item['description'],
                    'price' => $item['price'],
                    'condition' => $item['condition'],
                    'status' => $item['status'],
                    'moderation_status' => 'active',
                ]
            );

            // Tambahkan gambar utama
            ProductImage::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'is_primary' => true,
                ],
                [
                    'path' => $item['image'],
                    'sort_order' => 0,
                ]
            );

            // Attach area COD berdasarkan kota penjual / area terdekat
            $matchingArea = CodArea::where('city', $item['cod_city'])->first()
                ?? CodArea::first();

            if ($matchingArea) {
                $product->codAreas()->syncWithoutDetaching([$matchingArea->id]);
            }
        }
    }
}

