<?php

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Product;
use App\Models\User;
use App\Notifications\NewMessage;
use App\Notifications\OfferDecided;
use App\Notifications\OfferReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seller = User::factory()->create();
    $this->buyer = User::factory()->create();
    $this->product = Product::factory()->for($this->seller, 'seller')->create();
    $this->conversation = Conversation::factory()->create([
        'buyer_id' => $this->buyer->id,
        'seller_id' => $this->seller->id,
        'product_id' => $this->product->id,
    ]);
});

test('a text message notifies the counterpart but not the sender', function () {
    Notification::fake();

    $this->actingAs($this->buyer)
        ->postJson(route('chat.messages.store', $this->conversation), ['body' => 'halo']);

    Notification::assertSentTo($this->seller, NewMessage::class);
    Notification::assertNotSentTo($this->buyer, NewMessage::class);
});

test('an offer notifies the seller', function () {
    Notification::fake();

    $this->actingAs($this->buyer)
        ->postJson(route('chat.messages.store', $this->conversation), [
            'type' => 'offer',
            'offer_amount' => 100000,
        ]);

    Notification::assertSentTo(
        $this->seller,
        fn (OfferReceived $n): bool => $n->offerAmount === 100000 && $n->conversationId === $this->conversation->id,
    );
});

test('an accepted offer notifies the buyer', function () {
    Notification::fake();
    $message = Message::factory()->offer(150000)->create([
        'conversation_id' => $this->conversation->id,
        'sender_id' => $this->buyer->id,
    ]);

    $this->actingAs($this->seller)
        ->postJson(route('chat.offers.accept', [$this->conversation, $message]));

    Notification::assertSentTo(
        $this->buyer,
        fn (OfferDecided $n): bool => $n->accepted === true,
    );
    Notification::assertNotSentTo($this->seller, OfferDecided::class);
});

test('a rejected offer notifies the buyer', function () {
    Notification::fake();
    $message = Message::factory()->offer(150000)->create([
        'conversation_id' => $this->conversation->id,
        'sender_id' => $this->buyer->id,
    ]);

    $this->actingAs($this->seller)
        ->postJson(route('chat.offers.reject', [$this->conversation, $message]));

    Notification::assertSentTo(
        $this->buyer,
        fn (OfferDecided $n): bool => $n->accepted === false,
    );
});

test('a user opens the notifications page and sees entries', function () {
    $this->actingAs($this->buyer);
    $this->conversation->buyer->notify(new NewMessage($this->seller->name, $this->conversation->id));

    $this->get(route('notifications.index'))
        ->assertOk()
        ->assertSee('Pesan baru dari');
});

test('marking all as read clears unread notifications', function () {
    $this->actingAs($this->buyer);
    $this->buyer->notify(new NewMessage($this->seller->name, $this->conversation->id));

    $this->post(route('notifications.readAll'))->assertRedirect();

    expect($this->buyer->unreadNotifications()->count())->toBe(0);
});
