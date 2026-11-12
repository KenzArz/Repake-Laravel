<?php

namespace Database\Factories;

use App\Models\Banner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Banner>
 */
class BannerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->words(3, true),
            'image_path' => 'images/repake-banner-notext.webp',
            'alt_text' => fake()->sentence(4),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 10),
        ];
    }

    public function nonaktif(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
