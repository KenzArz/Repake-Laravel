<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CodArea extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'city',
        'province',
        'boundary',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'boundary' => 'array',
        ];
    }

    /**
     * Relasi ke produk yang melayani area COD ini
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_cod_areas', 'cod_area_id', 'product_id');
    }
}
