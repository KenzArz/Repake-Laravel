<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductImage>
 */
class ProductImageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'path' => 'products/placeholder.jpg',
            'is_cover' => false,
            'urutan' => 0,
        ];
    }

    public function cover(): static
    {
        return $this->state(fn (): array => ['is_cover' => true]);
    }
}
