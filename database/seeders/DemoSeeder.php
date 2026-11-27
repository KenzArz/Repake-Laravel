<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\CodArea;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Order;
use App\Models\Product;
use App\Models\Report;
use App\Models\Review;
use App\Models\User;
use App\Notifications\NewMessage;
use App\Notifications\OfferDecided;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Data demo penilaian.
 *
 * Sengaja dipisah dari DatabaseSeeder produksi dan hanya dijalankan manual
 * (`migrate:fresh --seed --seeder=DemoSeeder` atau `demo:reset`). Cerita yang
 * dibangun: tiga percakapan dengan state berbeda — tawaran pending, order
 * menunggu COD, dan order selesai berikut ulasan dua arah — supaya juri bisa
 * mencoba seluruh alur tanpa menyiapkan data sendiri.
 *
 * Prasyarat tampilan: `php artisan storage:link` agar foto produk di
 * storage/app/public/products bisa diakses browser.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // Kategori, zona COD, dan banner adalah data inti yang juga dipakai
        // instance produksi, jadi demo memakai seeder yang sama.
        $this->call([
            CategorySeeder::class,
            CodAreaSeeder::class,
            BannerSeeder::class,
        ]);

        [$pembeli, $penjual, $admin] = $this->createUsers();
        $products = $this->createProducts($penjual);
        $this->attachCodAreas($products);
        $this->createConversations($pembeli, $penjual, $products);
        $this->createReports($pembeli, $admin, $products);
        $this->createNotifications($pembeli);
    }

    /**
     * Tiga persona demo. Email diambil dari config demo agar login persona
     * dan data seed tidak pernah berbeda.
     *
     * @return array{0: User, 1: User, 2: User}
     */
    private function createUsers(): array
    {
        $pembeli = User::factory()->create([
            'name' => 'Juri Pembeli',
            'email' => config('demo.personas.pembeli'),
            'username' => 'juri.pembeli',
            'phone' => '081200000001',
            'kota' => 'Karawang',
            'bio' => 'Mahasiswa yang sedang mencari barang bekas berkualitas di sekitar kampus.',
        ]);

        $penjual = User::factory()->create([
            'name' => 'Juri Penjual',
            'email' => config('demo.personas.penjual'),
            'username' => 'juri.penjual',
            'phone' => '081200000002',
            'kota' => 'Karawang',
            'bio' => 'Mahasiswa yang menjual barang layak pakai dengan harga ramah kantong.',
        ]);

        $admin = User::factory()->admin()->create([
            'name' => 'Admin RePake',
            'email' => config('demo.personas.admin'),
            'username' => 'juri.admin',
            'phone' => '081200000003',
            'kota' => 'Karawang',
            'bio' => 'Moderator konten dan transaksi RePake.',
        ]);

        return [$pembeli, $penjual, $admin];
    }

    /**
     * 14 produk yang tersebar di seluruh kategori, memakai foto yang sudah
     * tersedia di storage/app/public/products (tanpa watermark).
     *
     * @return array<string, Product>
     */
    private function createProducts(User $penjual): array
    {
        $categories = Category::query()->pluck('id', 'slug');
        $products = [];
        $items = $this->productData();
        $total = count($items);
        $index = 0;

        foreach ($items as $key => $item) {
            // Waktu unggah dibuat bertingkat agar urutan "terbaru" di beranda
            // deterministik dan terasa natural saat demo.
            $createdAt = now()->subMinutes($total - $index);

            $product = Product::query()->forceCreate([
                'user_id' => $penjual->id,
                'category_id' => $categories[$item['kategori']] ?? null,
                'judul' => $item['judul'],
                'harga' => $item['harga'],
                'kondisi' => $item['kondisi'],
                'status' => $item['status'],
                'deskripsi' => $item['deskripsi'],
                'kota' => $item['kota'],
                'patokan_lokasi' => $item['patokan_lokasi'],
                'is_negotiable' => $item['is_negotiable'],
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            $product->images()->create([
                'path' => 'products/'.$item['gambar'],
                'is_cover' => true,
                'urutan' => 0,
            ]);

            $products[$key] = $product;
            $index++;
        }

        return $products;
    }

    /**
     * Setiap produk melayani titik temu yang sama dengan patokan lokasinya.
     *
     * @param  array<string, Product>  $products
     */
    private function attachCodAreas(array $products): void
    {
        $areas = CodArea::query()->pluck('id', 'name');

        foreach ($products as $product) {
            $areaId = $areas[$product->patokan_lokasi] ?? null;

            if ($areaId !== null) {
                $product->codAreas()->sync([$areaId]);
            }
        }
    }

    /**
     * Tiga percakapan dengan state berbeda agar semua tahap transaksi terlihat.
     *
     * @param  array<string, Product>  $products
     */
    private function createConversations(User $pembeli, User $penjual, array $products): void
    {
        // (a) Tawaran masih pending — titik masuk skrip demo untuk mencoba
        //     menerima/menolak tawaran dari sisi penjual.
        $pending = Conversation::query()->create([
            'buyer_id' => $pembeli->id,
            'seller_id' => $penjual->id,
            'product_id' => $products['monitor-lg']->id,
            'last_message_at' => now()->subHours(2),
        ]);

        $this->addMessages($pending, now()->subHours(3), [
            [$pembeli, Message::TYPE_TEXT, 'Halo kak, monitor LG-nya masih ready?', null, null, true],
            [$penjual, Message::TYPE_TEXT, 'Ready kak. Kondisi normal, tidak ada dead pixel.', null, null, true],
            [$pembeli, Message::TYPE_OFFER, null, 1_000_000, Message::OFFER_PENDING, false],
            [$penjual, Message::TYPE_TEXT, 'Boleh saya pertimbangkan dulu ya kak?', null, null, false],
        ]);

        // (b) Tawaran diterima, order menunggu COD.
        $agreed = Conversation::query()->create([
            'buyer_id' => $pembeli->id,
            'seller_id' => $penjual->id,
            'product_id' => $products['laptop-asus']->id,
            'last_message_at' => now()->subDay(),
        ]);

        $this->addMessages($agreed, now()->subDay()->subHour(), [
            [$pembeli, Message::TYPE_TEXT, 'Kak, laptop Vivobook masih ada?', null, null, true],
            [$penjual, Message::TYPE_TEXT, 'Masih kak, lengkap charger dan dus original.', null, null, true],
            [$pembeli, Message::TYPE_OFFER, null, 4_000_000, Message::OFFER_ACCEPTED, true],
            [$penjual, Message::TYPE_TEXT, 'Oke kak, COD di Kampus 1 UNSIKA besok sore ya.', null, null, false],
            [$pembeli, Message::TYPE_TEXT, 'Siap kak, saya konfirmasi lagi besok.', null, null, false],
        ]);

        Order::query()->create([
            'conversation_id' => $agreed->id,
            'buyer_id' => $pembeli->id,
            'seller_id' => $penjual->id,
            'product_id' => $products['laptop-asus']->id,
            'harga_final' => 4_000_000,
            'status' => Order::STATUS_DISEPAKATI,
        ]);

        // (c) Order selesai + ulasan dua arah. Produk ditandai terjual karena
        //     stok habis, sehingga riwayat transaksi ikut terisi.
        $done = Conversation::query()->create([
            'buyer_id' => $pembeli->id,
            'seller_id' => $penjual->id,
            'product_id' => $products['galaxy-buds2']->id,
            'last_message_at' => now()->subDays(3),
        ]);

        $this->addMessages($done, now()->subDays(3)->subHour(), [
            [$pembeli, Message::TYPE_TEXT, 'Buds2 Pro masih ready kak?', null, null, true],
            [$penjual, Message::TYPE_TEXT, 'Ready, mulus 95% dan lengkap dus.', null, null, true],
            [$pembeli, Message::TYPE_OFFER, null, 900_000, Message::OFFER_ACCEPTED, true],
            [$penjual, Message::TYPE_TEXT, 'Boleh kak, COD Grand Metropolitan Bekasi.', null, null, true],
            [$pembeli, Message::TYPE_TEXT, 'Sudah saya terima, barang sesuai. Makasih kak!', null, null, true],
        ]);

        $order = Order::query()->create([
            'conversation_id' => $done->id,
            'buyer_id' => $pembeli->id,
            'seller_id' => $penjual->id,
            'product_id' => $products['galaxy-buds2']->id,
            'harga_final' => 900_000,
            'status' => Order::STATUS_SELESAI,
            'completed_at' => now()->subDays(3),
            'completion_type' => Order::COMPLETION_STOCK_OUT,
            'completed_by' => Order::COMPLETED_BY_BUYER,
        ]);

        Review::query()->create([
            'order_id' => $order->id,
            'product_id' => $products['galaxy-buds2']->id,
            'reviewer_id' => $pembeli->id,
            'reviewee_id' => $penjual->id,
            'rating' => 5,
            'komentar' => 'Barang sesuai deskripsi, packing rapi, dan penjual responsif.',
        ]);

        Review::query()->create([
            'order_id' => $order->id,
            'product_id' => null,
            'reviewer_id' => $penjual->id,
            'reviewee_id' => $pembeli->id,
            'rating' => 5,
            'komentar' => 'Pembeli tepat waktu dan komunikasinya enak.',
        ]);
    }

    /**
     * Dua laporan: satu menunggu moderasi, satu sudah ditinjau admin.
     *
     * @param  array<string, Product>  $products
     */
    private function createReports(User $pembeli, User $admin, array $products): void
    {
        Report::query()->create([
            'product_id' => $products['powerbank-anker']->id,
            'reporter_id' => $pembeli->id,
            'alasan' => Report::REASON_BARANG_PALSU,
            'keterangan' => 'Kapasitas powerbank jauh di bawah klaim dan casing terlihat seperti tiruan.',
            'status' => Report::STATUS_PENDING,
        ]);

        Report::query()->create([
            'product_id' => $products['helm-kyt']->id,
            'reporter_id' => $pembeli->id,
            'alasan' => Report::REASON_HARGA_TIDAK_WAJAR,
            'keterangan' => 'Harga helm jauh di atas pasaran untuk kondisi yang sama.',
            'status' => Report::STATUS_REVIEWED,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now()->subDay(),
        ]);
    }

    /**
     * Tiga notifikasi belum dibaca agar badge lonceng langsung hidup.
     */
    private function createNotifications(User $pembeli): void
    {
        $conversations = Conversation::query()->orderBy('id')->pluck('id');

        foreach ($conversations->take(2) as $conversationId) {
            $pembeli->notify(new NewMessage('Juri Penjual', (int) $conversationId));
        }

        $pembeli->notify(new OfferDecided(
            accepted: true,
            offerAmount: 900_000,
            conversationId: (int) $conversations->last(),
        ));
    }

    /**
     * Tulis pesan berurutan dengan jarak waktu 5 menit agar riwayat chat
     * terbaca natural. `read_at` menentukan centang biru pada skrip demo.
     *
     * @param  array<int, array{0: User, 1: string, 2: ?string, 3: ?int, 4: ?string, 5: bool}>  $messages
     */
    private function addMessages(
        Conversation $conversation,
        \DateTimeInterface $start,
        array $messages,
    ): void {
        foreach ($messages as $index => $message) {
            [$sender, $type, $body, $amount, $offerStatus, $read] = $message;
            $sentAt = Carbon::instance($start)->addMinutes($index * 5);

            Message::query()->forceCreate([
                'conversation_id' => $conversation->id,
                'sender_id' => $sender->id,
                'client_id' => 'demo-'.$conversation->id.'-'.$index,
                'body' => $body,
                'type' => $type,
                'offer_amount' => $amount,
                'offer_status' => $offerStatus,
                'read_at' => $read ? $sentAt->copy()->addMinutes(2) : null,
                'delivered_at' => $sentAt->copy()->addMinute(),
                'created_at' => $sentAt,
                'updated_at' => $sentAt,
            ]);
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function productData(): array
    {
        return [
            'laptop-asus' => [
                'kategori' => 'elektronik',
                'judul' => 'Laptop ASUS Vivobook 14 Ryzen 5 5500U',
                'harga' => 4_250_000,
                'kondisi' => 'Seperti baru',
                'status' => Product::STATUS_DIJUAL,
                'deskripsi' => 'Laptop ASUS Vivobook 14 (A416KA), RAM 8GB, SSD 512GB, prosesor Ryzen 5 5500U. Pemakaian pribadi satu tahun, baterai masih sehat, lengkap charger dan dus.',
                'kota' => 'Karawang',
                'patokan_lokasi' => 'Kampus 1 UNSIKA Telukjambe',
                'is_negotiable' => true,
                'gambar' => 'laptop-asus-vivobook-14-ryzen-5-5500u.png',
            ],
            'monitor-lg' => [
                'kategori' => 'elektronik',
                'judul' => 'Monitor LG 24MK430H 24 Inch IPS',
                'harga' => 1_150_000,
                'kondisi' => 'Dirawat dengan baik',
                'status' => Product::STATUS_DIJUAL,
                'deskripsi' => 'Monitor LG 24MK430H ukuran 24 inci, panel IPS, refresh rate 75Hz. Tidak ada dead pixel, kabel HDMI dan adaptor termasuk.',
                'kota' => 'Karawang',
                'patokan_lokasi' => 'Kampus 1 UNSIKA Telukjambe',
                'is_negotiable' => true,
                'gambar' => 'monitor-lg-24mk430h-24-inch-ips.png',
            ],
            'galaxy-buds2' => [
                'kategori' => 'elektronik',
                'judul' => 'Samsung Galaxy Buds2 Pro',
                'harga' => 950_000,
                'kondisi' => 'Seperti baru',
                'status' => Product::STATUS_TERJUAL,
                'deskripsi' => 'Galaxy Buds2 Pro kondisi 95% mulus, ANC normal, lengkap dus dan kabel original. Sudah terjual lewat COD.',
                'kota' => 'Bekasi',
                'patokan_lokasi' => 'Grand Metropolitan Bekasi',
                'is_negotiable' => true,
                'gambar' => 'samsung-galaxy-buds2-pro.png',
            ],
            'powerbank-anker' => [
                'kategori' => 'elektronik',
                'judul' => 'Powerbank Anker PowerCore 20000mAh',
                'harga' => 285_000,
                'kondisi' => 'Seperti baru',
                'status' => Product::STATUS_DITINJAU,
                'deskripsi' => 'Powerbank Anker PowerCore 20000mAh, fast charging 20W, kelengkapan kabel dan pouch. Sedang dalam peninjauan admin karena ada laporan.',
                'kota' => 'Bekasi',
                'patokan_lokasi' => 'Summarecon Mall Bekasi',
                'is_negotiable' => true,
                'gambar' => 'powerbank-anker-powercore-20000mah.png',
            ],
            'jaket-varsity' => [
                'kategori' => 'pakaian',
                'judul' => 'Jaket Varsity Unisex Size L',
                'harga' => 120_000,
                'kondisi' => 'Layak pakai',
                'status' => Product::STATUS_DIJUAL,
                'deskripsi' => 'Jaket varsity unisex ukuran L, bahan tebal, tidak ada sobek atau bolong. Sudah dicuci sebelum dijual.',
                'kota' => 'Karawang',
                'patokan_lokasi' => 'Galuh Mas & Festive Walk',
                'is_negotiable' => true,
                'gambar' => 'jaket-varsity-unisex-size-l.png',
            ],
            'kemeja-flanel' => [
                'kategori' => 'pakaian',
                'judul' => 'Kemeja Flanel Preloved Motif Kotak',
                'harga' => 65_000,
                'kondisi' => 'Layak pakai',
                'status' => Product::STATUS_DIJUAL,
                'deskripsi' => 'Kemeja flanel motif kotak ukuran M, bahan adem, kancing lengkap. Ada bekas pemakaian wajar.',
                'kota' => 'Karawang',
                'patokan_lokasi' => 'Kampus 2 UNSIKA Klari',
                'is_negotiable' => false,
                'gambar' => 'kemeja-flanel-preloved-motif-kotak.png',
            ],
            'meja-belajar' => [
                'kategori' => 'perabot',
                'judul' => 'Meja Belajar Lipat Kayu',
                'harga' => 350_000,
                'kondisi' => 'Dirawat dengan baik',
                'status' => Product::STATUS_DIJUAL,
                'deskripsi' => 'Meja belajar lipat dari kayu, kokoh dan mudah disimpan. Cocok untuk kamar kos, permukaan masih rapi.',
                'kota' => 'Karawang',
                'patokan_lokasi' => 'Kampus 1 UNSIKA Telukjambe',
                'is_negotiable' => true,
                'gambar' => 'meja-belajar-lipat-kayu.png',
            ],
            'rak-buku' => [
                'kategori' => 'perabot',
                'judul' => 'Rak Buku Minimalis 4 Tingkat',
                'harga' => 275_000,
                'kondisi' => 'Layak pakai',
                'status' => Product::STATUS_DIJUAL,
                'deskripsi' => 'Rak buku minimalis empat tingkat, bahan kayu lapis. Ada sedikit goresan di sisi belakang, tetap kokoh.',
                'kota' => 'Bandung',
                'patokan_lokasi' => 'Dago & Simpang ITB',
                'is_negotiable' => true,
                'gambar' => 'rak-buku-minimalis-4-tingkat.png',
            ],
            'sepeda-mtb' => [
                'kategori' => 'olahraga',
                'judul' => 'Sepeda MTB Polygon Xtrada 5',
                'harga' => 2_850_000,
                'kondisi' => 'Dirawat dengan baik',
                'status' => Product::STATUS_DIJUAL,
                'deskripsi' => 'Sepeda MTB Polygon Xtrada 5 ukuran M, rem dan gear normal, ban masih tebal. Rutin diservis tiap tiga bulan.',
                'kota' => 'Karawang',
                'patokan_lokasi' => 'Galuh Mas & Festive Walk',
                'is_negotiable' => true,
                'gambar' => 'sepeda-mtb-polygon-xtrada-5.png',
            ],
            'dumbbell-5kg' => [
                'kategori' => 'olahraga',
                'judul' => 'Dumbbell 5kg Sepasang',
                'harga' => 130_000,
                'kondisi' => 'Layak pakai',
                'status' => Product::STATUS_DIJUAL,
                'deskripsi' => 'Dumbbell 5kg sepasang, grip karet nyaman. Ada bekas pemakaian wajar, tidak ada yang pecah.',
                'kota' => 'Karawang',
                'patokan_lokasi' => 'Kampus 2 UNSIKA Klari',
                'is_negotiable' => true,
                'gambar' => 'dumbbell-5kg-sepasang.png',
            ],
            'buku-kalkulus' => [
                'kategori' => 'buku',
                'judul' => 'Buku Kalkulus James Stewart Edisi 7',
                'harga' => 150_000,
                'kondisi' => 'Dirawat dengan baik',
                'status' => Product::STATUS_DIJUAL,
                'deskripsi' => 'Buku Kalkulus James Stewart edisi 7 jilid 1. Halaman lengkap, ada sedikit coretan stabilo di beberapa bab.',
                'kota' => 'Bandung',
                'patokan_lokasi' => 'Dago & Simpang ITB',
                'is_negotiable' => true,
                'gambar' => 'buku-kalkulus-james-stewart-edisi-7.png',
            ],
            'gitar-yamaha' => [
                'kategori' => 'hobi',
                'judul' => 'Gitar Akustik Yamaha F310',
                'harga' => 850_000,
                'kondisi' => 'Dirawat dengan baik',
                'status' => Product::STATUS_DIJUAL,
                'deskripsi' => 'Gitar akustik Yamaha F310, suara masih jernih, neck lurus. Termasuk softcase dan capo.',
                'kota' => 'Jakarta Pusat',
                'patokan_lokasi' => 'Monas & Thamrin',
                'is_negotiable' => true,
                'gambar' => 'gitar-akustik-yamaha-f310.png',
            ],
            'helm-kyt' => [
                'kategori' => 'otomotif',
                'judul' => 'Helm KYT Half Face',
                'harga' => 180_000,
                'kondisi' => 'Layak pakai',
                'status' => Product::STATUS_DIJUAL,
                'deskripsi' => 'Helm KYT half face ukuran L, busa masih tebal, visor bening ada baret halus. Sudah dicuci.',
                'kota' => 'Karawang',
                'patokan_lokasi' => 'Kampus 1 UNSIKA Telukjambe',
                'is_negotiable' => false,
                'gambar' => 'helm-kyt-half-face.png',
            ],
            'tumbler-corkcicle' => [
                'kategori' => 'lainnya',
                'judul' => 'Tumbler Corkcicle 750ml',
                'harga' => 210_000,
                'kondisi' => 'Seperti baru',
                'status' => Product::STATUS_DIJUAL,
                'deskripsi' => 'Tumbler Corkcicle 750ml, tahan panas dan dingin, tutup rapat. Baru dipakai beberapa kali.',
                'kota' => 'Jakarta Selatan',
                'patokan_lokasi' => 'Blok M Hub',
                'is_negotiable' => false,
                'gambar' => 'tumbler-corkcicle-750ml.png',
            ],
        ];
    }
}
