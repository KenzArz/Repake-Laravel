<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CodArea extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'city',
        'province',
        'boundary',
    ];

    protected function casts(): array
    {
        return [
            'boundary' => 'array',
        ];
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_cod_areas', 'cod_area_id', 'product_id');
    }

    /**
     * URL Google Maps dari data koordinat boundary [lng, lat]
     */
    public function getGmapsUrlAttribute(): string
    {
        $coords = $this->boundary['coordinates'] ?? null;
        if (is_array($coords) && count($coords) === 2) {
            // Reverse [lng, lat] -> lat,lng
            return "https://www.google.com/maps?q={$coords[1]},{$coords[0]}";
        }

        return "https://www.google.com/maps/search/?api=1&query=" . urlencode($this->name . ' ' . $this->city);
    }
}
