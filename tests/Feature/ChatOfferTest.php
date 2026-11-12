<?php

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

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

test('the buyer sends an offer without a body', function () {
    $this->actingAs($this->buyer)
        ->postJson(route('chat.messages.store', $this->conversation), [
            'type' => 'offer',
            'offer_amount' => 100000,
        ])
        ->assertOk()
        ->assertJsonPath('message.type', 'offer')
        ->assertJsonPath('message.offer_amount', 100000)
        ->assertJsonPath('message.offer_status', 'pending');

    $message = $this->conversation->messages()->sole();

    expect($message->offer_status)->toBe(Message::OFFER_PENDING)
        ->and($message->offer_amount)->toBe(100000);
});

test('an offer requires an amount', function () {
    $this->actingAs($this->buyer);

    $this->post(route('chat.messages.store', $this->conversation), [
        'type' => 'offer',
    ])->assertSessionHasErrors(['offer_amount']);
});

test('only the buyer can send an offer', function () {
    $this->actingAs($this->seller);

    $this->post(route('chat.messages.store', $this->conversation), [
        'type' => 'offer',
        'offer_amount' => 100000,
    ])->assertForbidden();

    expect($this->conversation->messages()->count())->toBe(0);
});

test('an offer from the product page starts the chat with a pending offer', function () {
    $this->actingAs($this->buyer);

    $response = $this->post(route('chat.store'), [
        'product_id' => $this->product->id,
        'offer_amount' => 75000,
    ]);

    $conversation = Conversation::query()->where('buyer_id', $this->buyer->id)->sole();
    $response->assertRedirect(route('chat.show', $conversation));

    $message = $conversation->messages()->sole();
    expect($message->type)->toBe(Message::TYPE_OFFER)
        ->and($message->offer_amount)->toBe(75000)
        ->and($message->offer_status)->toBe(Message::OFFER_PENDING);
});

test('an invalid offer amount from the product page starts nothing', function () {
    $this->actingAs($this->buyer);

    $this->post(route('chat.store'), [
        'product_id' => $this->product->id,
        'offer_amount' => -5,
    ])->assertSessionHasErrors(['offer_amount']);

    // beforeEach sudah membuat 1 percakapan; tidak boleh bertambah dan tetap tanpa pesan.
    expect($this->buyer->conversationsAsBuyer()->count())->toBe(1)
        ->and($this->conversation->messages()->count())->toBe(0);
});

test('the offer sender edits their pending offer amount', function () {
    $message = Message::factory()->offer(150000)->create([
        'conversation_id' => $this->conversation->id,
        'sender_id' => $this->buyer->id,
    ]);

    $this->actingAs($this->buyer)
        ->putJson(route('chat.offers.update', [$this->conversation, $message]), ['offer_amount' => 120000])
        ->assertOk()
        ->assertJsonPath('message.offer_amount', 120000);

    expect($message->refresh()->offer_amount)->toBe(120000)
        ->and($message->offer_status)->toBe(Message::OFFER_PENDING);
});

test('the offer sender cancels their pending offer', function () {
    $message = Message::factory()->offer(150000)->create([
        'conversation_id' => $this->conversation->id,
        'sender_id' => $this->buyer->id,
    ]);

    $this->actingAs($this->buyer)
        ->postJson(route('chat.offers.cancel', [$this->conversation, $message]))
        ->assertOk()
        ->assertJsonPath('message.offer_status', 'cancelled');

    expect($message->refresh()->offer_status)->toBe(Message::OFFER_CANCELLED)
        ->and(Order::count())->toBe(0);
});

test('only the offer sender can edit or cancel it', function () {
    $message = Message::factory()->offer(150000)->create([
        'conversation_id' => $this->conversation->id,
        'sender_id' => $this->buyer->id,
    ]);

    $this->actingAs($this->seller)
        ->put(route('chat.offers.update', [$this->conversation, $message]), ['offer_amount' => 1000])
        ->assertForbidden();

    $this->actingAs($this->seller)
        ->post(route('chat.offers.cancel', [$this->conversation, $message]))
        ->assertForbidden();

    expect($message->refresh()->offer_amount)->toBe(150000)
        ->and($message->offer_status)->toBe(Message::OFFER_PENDING);
});

test('a decided offer can no longer be edited or cancelled', function () {
    $message = Message::factory()->offer(150000)->create([
        'conversation_id' => $this->conversation->id,
        'sender_id' => $this->buyer->id,
        'offer_status' => Message::OFFER_ACCEPTED,
    ]);

    $this->actingAs($this->buyer)
        ->put(route('chat.offers.update', [$this->conversation, $message]), ['offer_amount' => 1000])
        ->assertStatus(409);

    $this->actingAs($this->buyer)
        ->post(route('chat.offers.cancel', [$this->conversation, $message]))
        ->assertStatus(409);

    expect($message->refresh()->offer_amount)->toBe(150000);
});

test('the seller accepts an offer and an order is created', function () {
    $message = Message::factory()->offer(150000)->create([
        'conversation_id' => $this->conversation->id,
        'sender_id' => $this->buyer->id,
    ]);

    $this->actingAs($this->seller)
        ->postJson(route('chat.offers.accept', [$this->conversation, $message]))
        ->assertOk()
        ->assertJsonPath('message.offer_status', 'accepted');

    $order = Order::sole();

    expect($message->refresh()->offer_status)->toBe(Message::OFFER_ACCEPTED)
        ->and($order->buyer_id)->toBe($this->buyer->id)
        ->and($order->seller_id)->toBe($this->seller->id)
        ->and($order->product_id)->toBe($this->product->id)
        ->and($order->harga_final)->toBe(150000)
        ->and($order->status)->toBe(Order::STATUS_DISEPAKATI)
        ->and($order->conversation_id)->toBe($this->conversation->id);
});

test('the buyer marks a disepakati order as selesai', function () {
    $message = Message::factory()->offer(150000)->create([
        'conversation_id' => $this->conversation->id,
        'sender_id' => $this->buyer->id,
    ]);

    $this->actingAs($this->seller)
        ->postJson(route('chat.offers.accept', [$this->conversation, $message]))
        ->assertOk()
        ->assertJsonPath('message.offer_status', 'accepted');

    $order = Order::sole();

    $this->actingAs($this->buyer)
        ->post(route('chat.complete', $this->conversation))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($order->refresh()->status)->toBe(Order::STATUS_SELESAI)
        ->and($order->completed_at)->not->toBeNull();
});

test('only the buyer can mark the order as selesai', function () {
    Order::factory()->create([
        'conversation_id' => $this->conversation->id,
        'seller_id' => $this->seller->id,
        'buyer_id' => $this->buyer->id,
        'product_id' => $this->product->id,
        'status' => Order::STATUS_DISEPAKATI,
    ]);

    $this->actingAs($this->seller)
        ->post(route('chat.complete', $this->conversation))
        ->assertForbidden();

    expect(Order::sole()->status)->toBe(Order::STATUS_DISEPAKATI);
});

test('a conversation without an order cannot be completed', function () {
    $this->actingAs($this->buyer)
        ->post(route('chat.complete', $this->conversation))
        ->assertNotFound();
});

test('an already selesai order cannot be completed again', function () {
    Order::factory()->selesai()->create([
        'conversation_id' => $this->conversation->id,
        'seller_id' => $this->seller->id,
        'buyer_id' => $this->buyer->id,
        'product_id' => $this->product->id,
    ]);

    $this->actingAs($this->buyer)
        ->post(route('chat.complete', $this->conversation))
        ->assertStatus(409);
});

test('the buyer cannot decide on their own offer', function () {
    $message = Message::factory()->offer(150000)->create([
        'conversation_id' => $this->conversation->id,
        'sender_id' => $this->buyer->id,
    ]);

    $this->actingAs($this->buyer)
        ->post(route('chat.offers.accept', [$this->conversation, $message]))
        ->assertForbidden();

    expect(Order::count())->toBe(0);
});

test('a non-participant cannot decide on an offer', function () {
    $message = Message::factory()->offer(150000)->create([
        'conversation_id' => $this->conversation->id,
        'sender_id' => $this->buyer->id,
    ]);

    $this->actingAs(User::factory()->create())
        ->post(route('chat.offers.accept', [$this->conversation, $message]))
        ->assertNotFound();
});

test('a pending offer can be decided only once', function () {
    $message = Message::factory()->offer(150000)->create([
        'conversation_id' => $this->conversation->id,
        'sender_id' => $this->buyer->id,
    ]);

    $this->actingAs($this->seller)
        ->postJson(route('chat.offers.accept', [$this->conversation, $message]))
        ->assertOk();

    $this->actingAs($this->seller)
        ->postJson(route('chat.offers.reject', [$this->conversation, $message]))
        ->assertStatus(409);

    expect(Order::count())->toBe(1)
        ->and($message->refresh()->offer_status)->toBe(Message::OFFER_ACCEPTED);
});

test('a text message cannot be decided as an offer', function () {
    $message = Message::factory()->create([
        'conversation_id' => $this->conversation->id,
        'sender_id' => $this->buyer->id,
        'body' => 'halo',
    ]);

    $this->actingAs($this->seller)
        ->post(route('chat.offers.accept', [$this->conversation, $message]))
        ->assertNotFound();
});

test('the seller rejects an offer and no order is created', function () {
    $message = Message::factory()->offer(150000)->create([
        'conversation_id' => $this->conversation->id,
        'sender_id' => $this->buyer->id,
    ]);

    $this->actingAs($this->seller)
        ->postJson(route('chat.offers.reject', [$this->conversation, $message]))
        ->assertOk()
        ->assertJsonPath('message.offer_status', 'rejected');

    expect(Order::count())->toBe(0)
        ->and($message->refresh()->offer_status)->toBe(Message::OFFER_REJECTED);
});
