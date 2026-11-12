<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * 12 produk demo. Gambar memakai path lokal di storage/app/public/products
 * (disediakan lewat `php artisan storage:link` → /storage/...).
 */
class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::pluck('id', 'slug');

        foreach ($this->products() as $item) {
            $seller = User::firstOrCreate(
                ['email' => $item['seller'].'@demo.unsika.ac.id'],
                ['name' => $item['seller']],
            );

            $product = Product::updateOrCreate(
                ['judul' => $item['judul']],
                [
                    'user_id' => $seller->id,
                    'category_id' => $categories[Str::slug($item['kategori'])] ?? null,
                    'judul' => $item['judul'],
                    'harga' => $item['harga'],
                    'kondisi' => $item['kondisi'],
                    'status' => Product::STATUS_DIJUAL,
                    'deskripsi' => $item['deskripsi'],
                    'kota' => $item['kota'],
                    'is_negotiable' => true,
                ],
            );

            $product->images()->updateOrCreate(
                ['path' => $item['gambar']],
                ['is_cover' => true, 'urutan' => 0],
            );
        }
    }

    /**
     * @return array<int, array<string, string|int>>
     */
    private function products(): array
    {
        return [
            [
                'seller' => 'TechStore_ID',
                'kategori' => 'Elektronik',
                'kota' => 'Jakarta Pusat',
                'judul' => 'Keyboard Mechanical RGB Gaming Bekas Mulus',
                'harga' => 450000,
                'kondisi' => 'Seperti baru',
                'deskripsi' => "Kondisi 98% sangat mulus pemakaian pribadi baru 3 bulan.\n- Switch Outemu Blue responsif dan renyah\n- RGB custom lighting dengan 18 mode efek\n- Lengkap kabel braided USB Type-C & keycap puller\n- Tidak ada minus fungsi, tombol semua normal dan responsif.",
                'gambar' => 'products/keyboard-mechanical.jpg',
            ],
            [
                'seller' => 'Midou_kun',
                'kategori' => 'Pakaian',
                'kota' => 'Bekasi',
                'judul' => 'Sepatu Sneakers Nike Air Force 1 Original Second',
                'harga' => 850000,
                'kondisi' => 'Dirawat dengan baik',
                'deskripsi' => "Sepatu Nike Air Force 1 100% Original pembelian di store resmi.\n- Ukuran: 42 (26.5 cm)\n- Warna: Wheat/Flax Coklat Premium\n- Tapak masih tebal, insole original, minus pemakaian wajar\n- Dilengkapi box original pengganti yang rapi.",
                'gambar' => 'products/nike-af1.jpg',
            ],
            [
                'seller' => 'VintageKuy',
                'kategori' => 'Pakaian',
                'kota' => 'Bandung',
                'judul' => 'Jaket Denim Vintage 90s Unisex Thrift Premium',
                'harga' => 180000,
                'kondisi' => 'Bekas pakai',
                'deskripsi' => "Jaket denim vintage look oversize style 90-an.\n- Bahan denim tebal berkualitas tidak kaku\n- Warna hitam washed original vintage\n- Size L (P: 68 cm, L: 58 cm), cocok untuk unisex pria/wanita\n- Sudah di-laundry wangi dan siap langsung pakai.",
                'gambar' => 'products/jaket-denim.jpg',
            ],
            [
                'seller' => 'CameraBekas_Pro',
                'kategori' => 'Elektronik',
                'kota' => 'Tangerang',
                'judul' => 'Kamera DSLR Canon EOS 70D Fullset Mulus Siap Pakai',
                'harga' => 4500000,
                'kondisi' => 'Seperti baru',
                'deskripsi' => "Canon EOS 70D kondisi mulus terawat 95%.\n- Shutter count rendah (< 12.xxx)\n- Layar flip touchscreen normal, sensor bersih dari jamur\n- Kelengkapan: Unit kamera, lensa kit 18-55mm IS STM, baterai ori, charger, strap, dan tas kamera.",
                'gambar' => 'products/kamera-canon-70d.jpg',
            ],
            [
                'seller' => 'TechStore_ID',
                'kategori' => 'Elektronik',
                'kota' => 'Jakarta Pusat',
                'judul' => 'Samsung Galaxy Earbuds Live True Wireless ANC',
                'harga' => 350000,
                'kondisi' => 'Dirawat dengan baik',
                'deskripsi' => "Samsung Galaxy Buds Live warna Mystic Bronze.\n- Suara jernih dengan bass mantap hasil tuning AKG\n- Active Noise Cancelling berfungsi normal\n- Baterai awet tahan seharian bersama case charging\n- Unit TWS, case, dan kabel charger Type-C.",
                'gambar' => 'products/galaxy-buds-live.jpg',
            ],
            [
                'seller' => 'UrbanOutfit',
                'kategori' => 'Pakaian',
                'kota' => 'Jakarta Selatan',
                'judul' => 'Kemeja Flannel Pria Motif Kotak Lengan Panjang',
                'harga' => 170000,
                'kondisi' => 'Dirawat dengan baik',
                'deskripsi' => "Kemeja flannel kasual bahan katun tebal dan lembut.\n- Warna kombinasi merah-hitam klasik\n- Ukuran M regular fit (nyaman untuk tinggi 165-175 cm)\n- Kancing lengkap dan jahitan rapi tanpa minus sobek.",
                'gambar' => 'products/kemeja-flanel.jpg',
            ],
            [
                'seller' => 'GamerCorner',
                'kategori' => 'Elektronik',
                'kota' => 'Surabaya',
                'judul' => 'Mouse Gaming Wireless Logitech G Pro X Superlight',
                'harga' => 950000,
                'kondisi' => 'Seperti baru',
                'deskripsi' => "Logitech G Pro X Superlight White mulus tanpa kendala.\n- Bobot super ringan < 63 gram\n- Sensor HERO 25K akurat tanpa delay\n- Klik renyah, scroll wheel empuk, skate PTFE masih licin\n- Kelengkapan fullset dus, receiver USB, dan kabel.",
                'gambar' => 'products/logitech-gprox.jpg',
            ],
            [
                'seller' => 'BookNook',
                'kategori' => 'Buku',
                'kota' => 'Yogyakarta',
                'judul' => 'Paket Novel Harry Potter 1-7 Bahasa Indonesia Segel',
                'harga' => 550000,
                'kondisi' => 'Dirawat dengan baik',
                'deskripsi' => "Paket komplit seri novel Harry Potter jilid 1 sampai 7.\n- Terbitan Gramedia Pustaka Utama berbahasa Indonesia\n- Halaman lengkap bersih tanpa coretan atau robekan\n- Kertas sedikit menguning alami khas koleksi novel vintage.",
                'gambar' => 'products/novel-harry-potter.jpg',
            ],
            [
                'seller' => 'GowesKuy_ID',
                'kategori' => 'Olahraga',
                'kota' => 'Tangerang Selatan',
                'judul' => 'Sepeda Lipat Polygon Urbano 3 Upgrade Shimano 8 Speed',
                'harga' => 2400000,
                'kondisi' => 'Dirawat dengan baik',
                'deskripsi' => "Polygon Urbano 3 warna Matte Black.\n- Frame alloy ringan dan kokoh, lipatan rapat tidak oblak\n- Shifter & RD sudah upgrade Shimano Altus 8 Speed lancar jaya\n- Ban tebal Kenda 20 inch, rem cakram mekanik pakem\n- Siap pakai untuk CFD atau mobilitas komuter harian.",
                'gambar' => 'products/sepeda-polygon.jpg',
            ],
            [
                'seller' => 'KosFurnitur',
                'kategori' => 'Perabot',
                'kota' => 'Depok',
                'judul' => 'Meja Belajar Minimalis Kayu Jati Belanda Kokoh',
                'harga' => 320000,
                'kondisi' => 'Seperti baru',
                'deskripsi' => "Meja kerja / meja belajar minimalis aesthetic.\n- Bahan solid wood kayu jati belanda serat natural vernis halus\n- Dimensi: Panjang 100 cm, Lebar 50 cm, Tinggi 75 cm\n- Kaki besi hollow kokoh anti karat dan anti goyang\n- Bisa dibongkar pasang mudah dibawa motor/mobil.",
                'gambar' => 'products/meja-kayu.jpg',
            ],
            [
                'seller' => 'GadgetSecond_BDG',
                'kategori' => 'Elektronik',
                'kota' => 'Bandung',
                'judul' => 'Smartwatch Amazfit GTS 2 Mini AMOLED Layar Mulus',
                'harga' => 490000,
                'kondisi' => 'Seperti baru',
                'deskripsi' => "Amazfit GTS 2 Mini warna Midnight Black.\n- Layar Always-On AMOLED jernih dan tajam tanpa baret\n- Sensor SpO2, Heart Rate, Sleep Tracker, GPS akurat\n- Baterai awet tahan 7-10 hari pemakaian normal\n- Lengkap strap cadangan, kabel charger magnetik, dan box.",
                'gambar' => 'products/amazfit-gts2.jpg',
            ],
            [
                'seller' => 'MelodyMusic',
                'kategori' => 'Hobi',
                'kota' => 'Jakarta Barat',
                'judul' => 'Gitar Akustik Yamaha F310 Original Softcase Busa Tebal',
                'harga' => 880000,
                'kondisi' => 'Dirawat dengan baik',
                'deskripsi' => "Yamaha F310 Natural original bukan custom abal-abal.\n- Suara nyaring dan sustain panjang khas Yamaha\n- Neck lurus, action ceper tidak sakit di jari, fretboard bersih\n- Senar D'Addario baru diganti\n- Bonus: Softcase busa tebal, capo, dan pick gitar.",
                'gambar' => 'products/gitar-yamaha-f310.jpg',
            ],
        ];
    }
}
