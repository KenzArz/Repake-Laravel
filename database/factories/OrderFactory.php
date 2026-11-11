<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'buyer_id' => User::factory(),
            'seller_id' => User::factory(),
            'product_id' => Product::factory(),
            'harga_final' => fake()->numberBetween(10_000, 5_000_000),
            'status' => Order::STATUS_DISEPAKATI,
        ];
    }

    public function selesai(): static
    {
        return $this->state(fn (): array => [
            'status' => Order::STATUS_SELESAI,
            'completed_at' => now(),
        ]);
    }

    public function batal(): static
    {
        return $this->state(fn (): array => ['status' => Order::STATUS_BATAL]);
    }
}
