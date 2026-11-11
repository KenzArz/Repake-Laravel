<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class Product extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'description',
        'price',
        'condition',
        'status',
        'moderation_status',
        'takedown_reason',
        'taken_down_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relasi Eloquent (Sesuai ERD)
    |--------------------------------------------------------------------------
    */

    /**
     * Relasi ke user pemilik / penjual produk
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Alias untuk relasi penjual
     */
    public function seller(): BelongsTo
    {
        return $this->user();
    }

    /**
     * Relasi ke kategori produk
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * Relasi ke seluruh gambar produk
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class, 'product_id')->orderBy('sort_order');
    }

    /**
     * Relasi ke gambar utama produk
     */
    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class, 'product_id')->where('is_primary', true);
    }

    /**
     * Relasi many-to-many ke area COD melalui pivot product_cod_areas
     */
    public function codAreas(): BelongsToMany
    {
        return $this->belongsToMany(CodArea::class, 'product_cod_areas', 'product_id', 'cod_area_id');
    }

    /**
     * Relasi ke daftar favorit
     */
    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class, 'product_id');
    }

    /**
     * Relasi ke percakapan chat untuk produk ini
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'product_id');
    }

    /**
     * Relasi ke laporan barang
     */
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'product_id');
    }

    /**
     * Relasi ke moderator/admin yang men-takedown produk
     */
    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'taken_down_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessor & Helper Tampilan (Blade UI)
    |--------------------------------------------------------------------------
    */

    /**
     * Format kondisi produk dari enum DB ke teks UI
     */
    protected function kondisi(): Attribute
    {
        return Attribute::make(
            get: fn(?string $value, array $attributes) => match ($attributes['condition'] ?? $value) {
                'baru' => 'Baru',
                'bekas_baik' => 'Seperti baru',
                'bekas_layak' => 'Bekas layak pakai',
                default => ucfirst($attributes['condition'] ?? ''),
            }
        );
    }

    /**
     * URL Gambar Utama / Cover Produk
     */
    protected function gambar(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $primary = $this->relationLoaded('primaryImage')
                    ? $this->primaryImage
                    : $this->images()->where('is_primary', true)->first();

                if ($primary) {
                    return $primary->path;
                }

                $firstImg = $this->relationLoaded('images')
                    ? $this->images->first()
                    : $this->images()->first();

                return $firstImg?->path ?? 'https://images.unsplash.com/photo-1595225476474-87563907a212?w=600&auto=format&fit=crop&q=80';
            }
        );
    }

    /**
     * Nama Penjual
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
     * Kota Asal Penjual / Area COD
     */
    protected function kota(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $seller = $this->relationLoaded('user') ? $this->user : $this->user()->first();
                if ($seller?->city) {
                    return $seller->city;
                }

                $cod = $this->relationLoaded('codAreas') ? $this->codAreas->first() : $this->codAreas()->first();

                return $cod?->city ?? 'Karawang';
            }
        );
    }

    /**
     * Status Favorit User
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
}
