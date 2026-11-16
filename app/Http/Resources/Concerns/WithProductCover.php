<?php

namespace App\Http\Resources\Concerns;

use App\Models\Product;
use App\Support\MediaUrl;

/**
 * Menghitung URL absolut gambar utama produk dari relasi yang sudah
 * di-eager load, tanpa memicu lazy-loading maupun path relatif.
 */
trait WithProductCover
{
    protected static function productCover(?Product $product): ?string
    {
        if ($product === null) {
            return null;
        }

        if ($product->relationLoaded('primaryImage') && $product->primaryImage) {
            return MediaUrl::make($product->primaryImage->path);
        }

        if ($product->relationLoaded('images') && $product->images->isNotEmpty()) {
            return MediaUrl::make($product->images->first()->path);
        }

        return null;
    }
}
