<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    public function definition(): array
    {
        $order = Order::factory()->selesai()->create();

        return [
            'order_id' => $order->id,
            'product_id' => $order->product_id,
            'reviewer_id' => $order->buyer_id,
            'seller_id' => $order->seller_id,
            'rating' => fake()->numberBetween(1, 5),
            'komentar' => fake()->sentence(),
        ];
    }
}
