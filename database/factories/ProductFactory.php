<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $judul = fake()->unique()->words(3, true);

        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'judul' => $judul,
            'slug' => Str::slug($judul).'-'.Str::random(6),
            'harga' => fake()->numberBetween(10_000, 5_000_000),
            'kondisi' => fake()->randomElement(['Seperti baru', 'Dirawat dengan baik', 'Bekas pakai']),
            'status' => Product::STATUS_DIJUAL,
            'deskripsi' => fake()->paragraph(),
            'kota' => fake()->city(),
            'patokan_lokasi' => null,
            'is_negotiable' => true,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => ['status' => Product::STATUS_DRAFT]);
    }

    public function terjual(): static
    {
        return $this->state(fn (): array => ['status' => Product::STATUS_TERJUAL]);
    }

    public function ditinjau(): static
    {
        return $this->state(fn (): array => ['status' => Product::STATUS_DITINJAU]);
    }
}
