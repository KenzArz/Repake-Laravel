<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Banner extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'image_path',
        'alt_text',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Banner yang sedang ditampilkan, sesuai urutan admin.
     */
    #[Scope]
    protected function aktif(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /**
     * URL gambar banner. Mendukung path publik lama (images/...) maupun
     * hasil unggahan baru di disk publik (storage/...).
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(function (): string {
            $path = (string) $this->image_path;

            if ($path === '') {
                return asset('images/repake-banner-notext.webp');
            }

            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
                return $path;
            }

            if (str_starts_with($path, '/')) {
                return asset(ltrim($path, '/'));
            }

            if (file_exists(public_path($path))) {
                return asset($path);
            }

            return Storage::url($path);
        });
    }
}
