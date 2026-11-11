<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Conversation>
 */
class ConversationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'buyer_id' => User::factory(),
            'seller_id' => User::factory(),
            'product_id' => Product::factory(),
            'last_message_at' => now(),
        ];
    }
}
