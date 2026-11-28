<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProductImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'path',
        'is_cover',
        'urutan',
    ];

    protected function casts(): array
    {
        return [
            'is_cover' => 'boolean',
            'urutan' => 'integer',
        ];
    }

    /**
     * URL publik untuk gambar.
     *
     * Data demo memakai URL Unsplash langsung, sedangkan unggahan pengguna
     * tetap tersimpan di disk publik dan diakses lewat symlink /storage.
     */
    protected function pathUrl(): Attribute
    {
        return Attribute::get(function (): string {
            $path = (string) $this->path;

            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
                return $path;
            }

            if (str_starts_with($path, '/')) {
                return asset(ltrim($path, '/'));
            }

            return Storage::url($path);
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
