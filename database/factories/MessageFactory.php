<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'sender_id' => User::factory(),
            'body' => fake()->sentence(),
            'type' => Message::TYPE_TEXT,
        ];
    }

    public function offer(int $amount): static
    {
        return $this->state(fn (): array => [
            'body' => null,
            'type' => Message::TYPE_OFFER,
            'offer_amount' => $amount,
            'offer_status' => Message::OFFER_PENDING,
        ]);
    }
}
