<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_DIJUAL = 'dijual';

    public const STATUS_TERJUAL = 'terjual';

    public const STATUS_DITINJAU = 'ditinjau';

    public const STATUS_DITANGGUIHKAN = 'ditangguhkan';

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
     * Produk yang masih ditawarkan dan penjualnya tidak sedang dibanned.
     */
    #[Scope]
    protected function dijual(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DIJUAL)
            ->whereHas('seller', fn (Builder $seller): Builder => $seller->where('is_banned', false));
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

    /**
     * Slug unik dari judul. Bila slug dasar sudah dipakai, tambahkan
     * sufiks angka (2, 3, ...) agar URL tetap rapi dan mudah dibaca.
     */
    public static function uniqueSlug(string $judul, ?string $id = null): string
    {
        $base = Str::slug($judul) ?: 'produk';
        $slug = $base;
        $suffix = 2;

        while (static::query()
            ->where('slug', $slug)
            ->when($id !== null, fn (Builder $query): Builder => $query->whereKeyNot($id))
            ->exists()
        ) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * URL kanonis halaman detail produk.
     *
     * Slug di path hanya untuk keterbacaan; UUID di query `id` adalah
     * identitas yang sebenarnya. Dengan begitu produk berjudul sama tetap
     * punya URL berbeda, dan rename judul tidak mematikan link yang beredar.
     */
    public function detailUrl(): string
    {
        return route('products.show', [
            'slug' => $this->slug,
            'id' => $this->id,
        ]);
    }

    protected static function booted(): void
    {
        static::creating(function (Product $product): void {
            if (blank($product->slug)) {
                $product->slug = static::uniqueSlug((string) $product->judul);
            }
        });

        static::updating(function (Product $product): void {
            if ($product->isDirty('judul') && ! $product->isDirty('slug')) {
                $product->slug = static::uniqueSlug((string) $product->judul, $product->id);
            }
        });
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
     * URL gambar utama produk dari database (dipakai product-card).
     */
    protected function gambar(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                $primary = $this->relationLoaded('primaryImage')
                    ? $this->primaryImage
                    : $this->primaryImage()->first();

                if ($primary?->path_url) {
                    return $primary->path_url;
                }

                $fallback = $this->relationLoaded('images')
                    ? $this->images->first()
                    : $this->images()->first();

                return $fallback?->path_url;
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
     * URL avatar penjual (dipakai product-card & halaman detail).
     */
    protected function penjualAvatar(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $seller = $this->relationLoaded('user') ? $this->user : $this->user()->first();

                return $seller?->avatar_url ?? asset('images/default-avatar.png');
            }
        );
    }

    /**
     * Label status untuk tampilan (dipakai product-card & halaman detail).
     */
    protected function statusLabel(): Attribute
    {
        return Attribute::make(
            get: fn (): string => match ($this->status) {
                self::STATUS_TERJUAL => 'Terjual',
                self::STATUS_DITINJAU => 'Ditinjau',
                self::STATUS_DITANGGUIHKAN => 'Ditangguhkan',
                self::STATUS_DRAFT => 'Draft',
                default => 'Dijual',
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

                return Auth::check()
                    && $this->favorites()->where('user_id', Auth::id())->exists();
            }
        );
    }
}
