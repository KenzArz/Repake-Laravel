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
     * URL publik untuk gambar (via symlink /storage).
     */
    protected function pathUrl(): Attribute
    {
        return Attribute::get(fn (): string => Storage::url($this->path));
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
