<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_DIJUAL = 'dijual';

    public const STATUS_TERJUAL = 'terjual';

    public const STATUS_DITINJAU = 'ditinjau';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'category_id',
        'judul',
        'slug',
        'harga',
        'kondisi',
        'status',
        'deskripsi',
        'kota',
        'patokan_lokasi',
        'is_negotiable',
    ];

    protected function casts(): array
    {
        return [
            'harga' => 'integer',
            'is_negotiable' => 'boolean',
        ];
    }

    /**
     * Produk yang masih ditawarkan.
     */
    #[Scope]
    protected function dijual(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DIJUAL);
    }

    /**
     * Filter teks pada judul dan deskripsi.
     */
    #[Scope]
    protected function cari(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        return $query->when($term !== '', fn (Builder $q): Builder => $q->where(
            fn (Builder $inner): Builder => $inner
                ->where('judul', 'like', "%{$term}%")
                ->orWhere('deskripsi', 'like', "%{$term}%")
        ));
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Alias relasi pemilik produk (dipakai eager load `user` di view teman).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('urutan');
    }

    public function coverImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->ofMany('urutan', 'MIN');
    }

    /**
     * Gambar utama (kolom is_cover). Nama relasi mengikuti kode teman.
     */
    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->where('is_cover', true);
    }

    /**
     * Area COD yang dilayani produk (tabel pivot product_cod_areas).
     */
    public function codAreas(): BelongsToMany
    {
        return $this->belongsToMany(CodArea::class, 'product_cod_areas', 'product_id', 'cod_area_id');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

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
                'kategori' => 'Elektronik',
                'deskripsi' => "Kondisi 98% sangat mulus pemakaian pribadi baru 3 bulan.\n- Switch Outemu Blue responsif dan renyah\n- RGB custom lighting dengan 18 mode efek\n- Lengkap kabel braided USB Type-C & keycap puller\n- Tidak ada minus fungsi, tombol semua normal dan responsif.",
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
                'kategori' => 'Pakaian',
                'deskripsi' => "Sepatu Nike Air Force 1 100% Original pembelian di store resmi.\n- Ukuran: 42 (26.5 cm)\n- Warna: Wheat/Flax Coklat Premium\n- Tapak masih tebal, insole original, minus pemakaian wajar\n- Dilengkapi box original pengganti yang rapi.",
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
                'kategori' => 'Pakaian',
                'deskripsi' => "Jaket denim vintage look oversize style 90-an.\n- Bahan denim tebal berkualitas tidak kaku\n- Warna hitam washed original vintage\n- Size L (P: 68 cm, L: 58 cm), cocok untuk unisex pria/wanita\n- Sudah di-laundry wangi dan siap langsung pakai.",
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
                'kategori' => 'Elektronik',
                'deskripsi' => "Canon EOS 70D kondisi mulus terawat 95%.\n- Shutter count rendah (< 12.xxx)\n- Layar flip touchscreen normal, sensor bersih dari jamur\n- Kelengkapan: Unit kamera, lensa kit 18-55mm IS STM, baterai ori, charger, strap, dan tas kamera.",
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
                'kategori' => 'Elektronik',
                'deskripsi' => "Samsung Galaxy Buds Live warna Mystic Bronze.\n- Suara jernih dengan bass mantap hasil tuning AKG\n- Active Noise Cancelling berfungsi normal\n- Baterai awet tahan seharian bersama case charging\n- Unit TWS, case, dan kabel charger Type-C.",
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
                'kategori' => 'Pakaian',
                'deskripsi' => "Kemeja flannel kasual bahan katun tebal dan lembut.\n- Warna kombinasi merah-hitam klasik\n- Ukuran M regular fit (nyaman untuk tinggi 165-175 cm)\n- Kancing lengkap dan jahitan rapi tanpa minus sobek.",
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
                'kategori' => 'Elektronik',
                'deskripsi' => "Logitech G Pro X Superlight White mulus tanpa kendala.\n- Bobot super ringan < 63 gram\n- Sensor HERO 25K akurat tanpa delay\n- Klik renyah, scroll wheel empuk, skate PTFE masih licin\n- Kelengkapan fullset dus, receiver USB, dan kabel.",
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
                'kategori' => 'Buku',
                'deskripsi' => "Paket komplit seri novel Harry Potter jilid 1 sampai 7.\n- Terbitan Gramedia Pustaka Utama berbahasa Indonesia\n- Halaman lengkap bersih tanpa coretan atau robekan\n- Kertas sedikit menguning alami khas koleksi novel vintage.",
                'gambar' => 'https://images.unsplash.com/photo-1512820790803-83ca734da794?w=600&auto=format&fit=crop&q=80',
            ],
            [
                'id' => 9,
                'penjual' => 'GowesKuy_ID',
                'kota' => 'Tangerang Selatan',
                'created_at' => Carbon::now()->subDays(4),
                'status' => 'Dijual',
                'judul' => 'Sepeda Lipat Polygon Urbano 3 Upgrade Shimano 8 Speed',
                'harga' => 2400000,
                'kondisi' => 'Dirawat dengan baik',
                'kategori' => 'Olahraga',
                'deskripsi' => "Polygon Urbano 3 warna Matte Black.\n- Frame alloy ringan dan kokoh, lipatan rapat tidak oblak\n- Shifter & RD sudah upgrade Shimano Altus 8 Speed lancar jaya\n- Ban tebal Kenda 20 inch, rem cakram mekanik pakem\n- Siap pakai untuk CFD atau mobilitas komuter harian.",
                'gambar' => 'https://images.unsplash.com/photo-1485965120184-e220f721d03e?w=600&auto=format&fit=crop&q=80',
            ],
            [
                'id' => 10,
                'penjual' => 'KosFurnitur',
                'kota' => 'Depok',
                'created_at' => Carbon::now()->subDays(5),
                'status' => 'Dijual',
                'judul' => 'Meja Belajar Minimalis Kayu Jati Belanda Kokoh',
                'harga' => 320000,
                'kondisi' => 'Seperti baru',
                'kategori' => 'Perabot',
                'deskripsi' => "Meja kerja / meja belajar minimalis aesthetic.\n- Bahan solid wood kayu jati belanda serat natural vernis halus\n- Dimensi: Panjang 100 cm, Lebar 50 cm, Tinggi 75 cm\n- Kaki besi hollow kokoh anti karat dan anti goyang\n- Bisa dibongkar pasang mudah dibawa motor/mobil.",
                'gambar' => 'https://images.unsplash.com/photo-1518455027359-f3f8164ba6bd?w=600&auto=format&fit=crop&q=80',
            ],
            [
                'id' => 11,
                'penjual' => 'GadgetSecond_BDG',
                'kota' => 'Bandung',
                'created_at' => Carbon::now()->subDays(5),
                'status' => 'Dijual',
                'judul' => 'Smartwatch Amazfit GTS 2 Mini AMOLED Layar Mulus',
                'harga' => 490000,
                'kondisi' => 'Seperti baru',
                'kategori' => 'Elektronik',
                'deskripsi' => "Amazfit GTS 2 Mini warna Midnight Black.\n- Layar Always-On AMOLED jernih dan tajam tanpa baret\n- Sensor SpO2, Heart Rate, Sleep Tracker, GPS akurat\n- Baterai awet tahan 7-10 hari pemakaian normal\n- Lengkap strap cadangan, kabel charger magnetik, dan box.",
                'gambar' => 'https://images.unsplash.com/photo-1508685096489-7aacd43bd3b1?w=600&auto=format&fit=crop&q=80',
            ],
            [
                'id' => 12,
                'penjual' => 'MelodyMusic',
                'kota' => 'Jakarta Barat',
                'created_at' => Carbon::now()->subDays(6),
                'status' => 'Dijual',
                'judul' => 'Gitar Akustik Yamaha F310 Original Softcase Busa Tebal',
                'harga' => 880000,
                'kondisi' => 'Dirawat dengan baik',
                'kategori' => 'Hobi',
                'deskripsi' => "Yamaha F310 Natural original bukan custom abal-abal.\n- Suara nyaring dan sustain panjang khas Yamaha\n- Neck lurus, action ceper tidak sakit di jari, fretboard bersih\n- Senar D'Addario baru diganti\n- Bonus: Softcase busa tebal, capo, dan pick gitar.",
                'gambar' => 'https://images.unsplash.com/photo-1510915361894-db8b60106cb1?w=600&auto=format&fit=crop&q=80',
            ],
        ];

        return collect($samples)->map(function (array $item) use ($favorites): object {
            $item['is_favorite'] = in_array($item['id'], $favorites, true);

            return (object) $item;
        });
    }

    /**
     * URL gambar utama produk dari database (dipakai product-card).
     */
    protected function gambar(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                $primary = $this->relationLoaded('primaryImage')
                    ? $this->primaryImage
                    : $this->primaryImage()->first();

                return $primary?->path_url ?? $this->images->first()?->path_url;
            }
        );
    }

    /**
     * Nama penjual (dipakai product-card).
     */
    protected function penjual(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $seller = $this->relationLoaded('user') ? $this->user : $this->user()->first();

                return $seller?->name ?? 'Penjual RePake';
            }
        );
    }

    /**
     * Status favorit untuk user yang sedang login (dipakai product-card).
     */
    protected function isFavorite(): Attribute
    {
        return Attribute::make(
            get: function (?bool $value): bool {
                if ($value !== null) {
                    return $value;
                }

                if (Auth::check()) {
                    return $this->favorites()->where('user_id', Auth::id())->exists();
                }

                return in_array($this->id, session()->get('favorites', []), true);
            }
        );
    }

    public static function findDummy(int|string $id): ?object
    {
        return static::dummyCatalog()->firstWhere('id', (int) $id);
    }
}
