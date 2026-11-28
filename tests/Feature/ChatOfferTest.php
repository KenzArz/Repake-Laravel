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
    $this->product = Product::factory()->for($this->seller, 'seller')->create([
        // Harga tetap supaya tawaran di test tidak pernah melebihi harga produk
        // (factory memakai harga acak 10rb–5jt dan itu bikin test flaky).
        'harga' => 1_000_000,
    ]);
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

test('the seller completes the order and marks the product as sold out', function () {
    $order = Order::factory()->create([
        'conversation_id' => $this->conversation->id,
        'seller_id' => $this->seller->id,
        'buyer_id' => $this->buyer->id,
        'product_id' => $this->product->id,
        'status' => Order::STATUS_DISEPAKATI,
    ]);

    $this->actingAs($this->seller)
        ->post(route('chat.sold', $this->conversation), ['completion_type' => Order::COMPLETION_STOCK_OUT])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($this->product->refresh()->status)->toBe(Product::STATUS_TERJUAL)
        ->and($order->refresh()->status)->toBe(Order::STATUS_SELESAI)
        ->and($order->completed_at)->not->toBeNull()
        ->and($order->completion_type)->toBe(Order::COMPLETION_STOCK_OUT);
});

test('the seller completes the order while keeping the stock available', function () {
    $order = Order::factory()->create([
        'conversation_id' => $this->conversation->id,
        'seller_id' => $this->seller->id,
        'buyer_id' => $this->buyer->id,
        'product_id' => $this->product->id,
        'status' => Order::STATUS_DISEPAKATI,
    ]);

    $this->actingAs($this->seller)
        ->post(route('chat.sold', $this->conversation), ['completion_type' => Order::COMPLETION_STOCK_AVAILABLE])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($this->product->refresh()->status)->toBe(Product::STATUS_DIJUAL)
        ->and($order->refresh()->status)->toBe(Order::STATUS_SELESAI)
        ->and($order->completion_type)->toBe(Order::COMPLETION_STOCK_AVAILABLE);
});

test('only the seller can complete the transaction', function () {
    Order::factory()->create([
        'conversation_id' => $this->conversation->id,
        'seller_id' => $this->seller->id,
        'buyer_id' => $this->buyer->id,
        'product_id' => $this->product->id,
        'status' => Order::STATUS_DISEPAKATI,
    ]);

    $this->actingAs($this->buyer)
        ->post(route('chat.sold', $this->conversation), ['completion_type' => Order::COMPLETION_STOCK_OUT])
        ->assertForbidden();

    expect($this->product->refresh()->status)->toBe(Product::STATUS_DIJUAL);
});

test('another agreed order for the same product is cancelled when the transaction is completed sold out', function () {
    $otherBuyer = User::factory()->create();
    $otherConversation = Conversation::factory()->create([
        'buyer_id' => $otherBuyer->id,
        'seller_id' => $this->seller->id,
        'product_id' => $this->product->id,
    ]);

    $order = Order::factory()->create([
        'conversation_id' => $this->conversation->id,
        'seller_id' => $this->seller->id,
        'buyer_id' => $this->buyer->id,
        'product_id' => $this->product->id,
        'status' => Order::STATUS_DISEPAKATI,
    ]);

    $otherOrder = Order::factory()->create([
        'conversation_id' => $otherConversation->id,
        'seller_id' => $this->seller->id,
        'buyer_id' => $otherBuyer->id,
        'product_id' => $this->product->id,
        'status' => Order::STATUS_DISEPAKATI,
    ]);

    $this->actingAs($this->seller)
        ->post(route('chat.sold', $this->conversation), ['completion_type' => Order::COMPLETION_STOCK_OUT])
        ->assertRedirect();

    expect($order->refresh()->status)->toBe(Order::STATUS_SELESAI)
        ->and($otherOrder->refresh()->status)->toBe(Order::STATUS_BATAL)
        ->and($otherOrder->cancel_reason)->toBe(Order::CANCEL_REASON_SOLD_ELSEWHERE)
        ->and($otherBuyer->notifications()->count())->toBe(1);
});

test('a completion that keeps stock available leaves other orders untouched', function () {
    $otherBuyer = User::factory()->create();
    $otherConversation = Conversation::factory()->create([
        'buyer_id' => $otherBuyer->id,
        'seller_id' => $this->seller->id,
        'product_id' => $this->product->id,
    ]);

    $order = Order::factory()->create([
        'conversation_id' => $this->conversation->id,
        'seller_id' => $this->seller->id,
        'buyer_id' => $this->buyer->id,
        'product_id' => $this->product->id,
        'status' => Order::STATUS_DISEPAKATI,
    ]);

    $otherOrder = Order::factory()->create([
        'conversation_id' => $otherConversation->id,
        'seller_id' => $this->seller->id,
        'buyer_id' => $otherBuyer->id,
        'product_id' => $this->product->id,
        'status' => Order::STATUS_DISEPAKATI,
    ]);

    $this->actingAs($this->seller)
        ->post(route('chat.sold', $this->conversation), ['completion_type' => Order::COMPLETION_STOCK_AVAILABLE])
        ->assertRedirect();

    expect($order->refresh()->status)->toBe(Order::STATUS_SELESAI)
        ->and($otherOrder->refresh()->status)->toBe(Order::STATUS_DISEPAKATI);
});

test('a conversation without an order cannot be completed', function () {
    $this->actingAs($this->seller)
        ->post(route('chat.sold', $this->conversation), ['completion_type' => Order::COMPLETION_STOCK_OUT])
        ->assertStatus(409);

    expect($this->product->refresh()->status)->toBe(Product::STATUS_DIJUAL);
});

test('an already sold product cannot be completed as sold out again', function () {
    $this->product->update(['status' => Product::STATUS_TERJUAL]);

    Order::factory()->create([
        'conversation_id' => $this->conversation->id,
        'seller_id' => $this->seller->id,
        'buyer_id' => $this->buyer->id,
        'product_id' => $this->product->id,
        'status' => Order::STATUS_DISEPAKATI,
    ]);

    $this->actingAs($this->seller)
        ->post(route('chat.sold', $this->conversation), ['completion_type' => Order::COMPLETION_STOCK_OUT])
        ->assertStatus(409);
});

test('a completion requires a valid completion type', function () {
    Order::factory()->create([
        'conversation_id' => $this->conversation->id,
        'seller_id' => $this->seller->id,
        'buyer_id' => $this->buyer->id,
        'product_id' => $this->product->id,
        'status' => Order::STATUS_DISEPAKATI,
    ]);

    $this->actingAs($this->seller)
        ->post(route('chat.sold', $this->conversation))
        ->assertSessionHasErrors(['completion_type']);
});

test('a second accepted offer on the same conversation does not create a duplicate order', function () {
    $first = Message::factory()->offer(150000)->create([
        'conversation_id' => $this->conversation->id,
        'sender_id' => $this->buyer->id,
    ]);

    $this->actingAs($this->seller)
        ->postJson(route('chat.offers.accept', [$this->conversation, $first]))
        ->assertOk();

    expect(Order::count())->toBe(1);

    // Buyer mengirim tawaran baru lalu penjual menerimanya → harus ditolak
    // karena percakapan sudah punya transaksi berjalan.
    $second = Message::factory()->offer(140000)->create([
        'conversation_id' => $this->conversation->id,
        'sender_id' => $this->buyer->id,
        'offer_status' => Message::OFFER_PENDING,
    ]);

    $this->actingAs($this->seller)
        ->postJson(route('chat.offers.accept', [$this->conversation, $second]))
        ->assertStatus(409);

    expect(Order::count())->toBe(1);
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

test('an offer is rejected while a transaction is still active', function () {
    Order::factory()->create([
        'conversation_id' => $this->conversation->id,
        'seller_id' => $this->seller->id,
        'buyer_id' => $this->buyer->id,
        'product_id' => $this->product->id,
        'status' => Order::STATUS_DISEPAKATI,
    ]);

    $this->actingAs($this->buyer)
        ->postJson(route('chat.messages.store', $this->conversation), [
            'type' => 'offer',
            'offer_amount' => 100000,
        ])
        ->assertStatus(409);

    expect($this->conversation->messages()->where('type', Message::TYPE_OFFER)->count())->toBe(0);
});

test('the buyer can buy again after a completed transaction with stock still available', function () {
    Order::factory()->create([
        'conversation_id' => $this->conversation->id,
        'seller_id' => $this->seller->id,
        'buyer_id' => $this->buyer->id,
        'product_id' => $this->product->id,
        'status' => Order::STATUS_SELESAI,
        'completed_at' => now(),
        'completion_type' => Order::COMPLETION_STOCK_AVAILABLE,
        'completed_by' => Order::COMPLETED_BY_SELLER,
    ]);

    $this->actingAs($this->buyer)
        ->postJson(route('chat.messages.store', $this->conversation), [
            'type' => 'offer',
            'offer_amount' => 90000,
        ])
        ->assertOk()
        ->assertJsonPath('message.offer_status', 'pending');

    $offer = $this->conversation->messages()->where('type', Message::TYPE_OFFER)->sole();

    $this->actingAs($this->seller)
        ->postJson(route('chat.offers.accept', [$this->conversation, $offer]))
        ->assertOk();

    expect(Order::query()->where('status', Order::STATUS_DISEPAKATI)->count())->toBe(1)
        ->and(Order::count())->toBe(2);
});

test('the buyer confirms receipt and completes the transaction herself', function () {
    $order = Order::factory()->create([
        'conversation_id' => $this->conversation->id,
        'seller_id' => $this->seller->id,
        'buyer_id' => $this->buyer->id,
        'product_id' => $this->product->id,
        'status' => Order::STATUS_DISEPAKATI,
    ]);

    $this->actingAs($this->buyer)
        ->patch(route('orders.update-status', $order), ['status' => Order::STATUS_SELESAI])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($order->refresh()->status)->toBe(Order::STATUS_SELESAI)
        ->and($order->completed_by)->toBe(Order::COMPLETED_BY_BUYER)
        ->and($order->completed_at)->not->toBeNull()
        ->and($this->product->refresh()->status)->toBe(Product::STATUS_DIJUAL)
        ->and($this->seller->notifications()->count())->toBe(1);
});

test('a non-participant cannot confirm receipt', function () {
    $order = Order::factory()->create([
        'conversation_id' => $this->conversation->id,
        'seller_id' => $this->seller->id,
        'buyer_id' => $this->buyer->id,
        'product_id' => $this->product->id,
        'status' => Order::STATUS_DISEPAKATI,
    ]);

    $this->actingAs(User::factory()->create())
        ->patch(route('orders.update-status', $order), ['status' => Order::STATUS_SELESAI])
        ->assertForbidden();

    expect($order->refresh()->status)->toBe(Order::STATUS_DISEPAKATI);
});

test('the seller closes the listing after the buyer confirmed receipt', function () {
    $order = Order::factory()->create([
        'conversation_id' => $this->conversation->id,
        'seller_id' => $this->seller->id,
        'buyer_id' => $this->buyer->id,
        'product_id' => $this->product->id,
        'status' => Order::STATUS_SELESAI,
        'completed_at' => now(),
        'completion_type' => null,
        'completed_by' => Order::COMPLETED_BY_BUYER,
    ]);

    $this->actingAs($this->seller)
        ->patch(route('orders.update-status', $order), [
            'status' => Order::STATUS_SELESAI,
            'completion_type' => Order::COMPLETION_STOCK_OUT,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($order->refresh()->completion_type)->toBe(Order::COMPLETION_STOCK_OUT)
        ->and($this->product->refresh()->status)->toBe(Product::STATUS_TERJUAL);
});

test('completing a transaction cancels offers that are still pending', function () {
    $offer = Message::factory()->offer(150000)->create([
        'conversation_id' => $this->conversation->id,
        'sender_id' => $this->buyer->id,
    ]);

    Order::factory()->create([
        'conversation_id' => $this->conversation->id,
        'seller_id' => $this->seller->id,
        'buyer_id' => $this->buyer->id,
        'product_id' => $this->product->id,
        'status' => Order::STATUS_DISEPAKATI,
    ]);

    $this->actingAs($this->seller)
        ->post(route('chat.sold', $this->conversation), [
            'completion_type' => Order::COMPLETION_STOCK_AVAILABLE,
        ])
        ->assertRedirect();

    expect($offer->refresh()->offer_status)->toBe(Message::OFFER_CANCELLED);
});
