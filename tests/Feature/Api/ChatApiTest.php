<?php

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/** @return array{seller: User, buyer: User, product: Product, conversation: Conversation} */
function apiChatFixture(): array
{
    $seller = User::factory()->create();
    $buyer = User::factory()->create();
    $product = Product::factory()->for($seller, 'seller')->create();
    $conversation = Conversation::factory()->create([
        'buyer_id' => $buyer->id,
        'seller_id' => $seller->id,
        'product_id' => $product->id,
    ]);

    return compact('seller', 'buyer', 'product', 'conversation');
}

test('pembeli dapat memulai percakapan dari produk', function () {
    ['buyer' => $buyer, 'product' => $product] = apiChatFixture();
    Sanctum::actingAs($buyer);

    $this->postJson('/api/v1/chats', ['product_id' => $product->id])
        ->assertOk()
        ->assertJsonPath('data.product.id', $product->id);
});

test('tidak bisa chat pada produk sendiri', function () {
    ['seller' => $seller, 'product' => $product] = apiChatFixture();
    Sanctum::actingAs($seller);

    $this->postJson('/api/v1/chats', ['product_id' => $product->id])->assertStatus(403);
});

test('peserta dapat mengirim pesan teks', function () {
    ['buyer' => $buyer, 'conversation' => $conversation] = apiChatFixture();
    Sanctum::actingAs($buyer);

    $this->postJson('/api/v1/chats/'.$conversation->id.'/messages', ['body' => 'Halo'])
        ->assertOk()
        ->assertJsonPath('data.type', Message::TYPE_TEXT);

    expect($conversation->messages()->count())->toBe(1);
});

test('hanya pembeli yang bisa mengirim tawaran', function () {
    ['seller' => $seller, 'conversation' => $conversation] = apiChatFixture();
    Sanctum::actingAs($seller);

    $this->postJson('/api/v1/chats/'.$conversation->id.'/messages', [
        'type' => 'offer',
        'offer_amount' => 100000,
    ])->assertStatus(403);
});

test('penjual menerima tawaran dan order dibuat', function () {
    ['seller' => $seller, 'buyer' => $buyer, 'product' => $product, 'conversation' => $conversation] = apiChatFixture();
    $message = Message::factory()->offer(150000)->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $buyer->id,
    ]);
    Sanctum::actingAs($seller);

    $this->postJson('/api/v1/chats/'.$conversation->id.'/messages/'.$message->id.'/accept')
        ->assertOk()
        ->assertJsonPath('data.message.offer_status', Message::OFFER_ACCEPTED)
        ->assertJsonPath('data.order.buyer.id', $buyer->id);

    $order = Order::sole();
    expect($order->product_id)->toBe($product->id)->and($order->status)->toBe(Order::STATUS_DISEPAKATI);
});

test('non-peserta tidak bisa melihat percakapan', function () {
    ['conversation' => $conversation] = apiChatFixture();
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/v1/chats/'.$conversation->id)->assertStatus(404);
});

test('polling mengembalikan pesan baru', function () {
    ['buyer' => $buyer, 'conversation' => $conversation] = apiChatFixture();
    $existing = Message::factory()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $buyer->id,
        'body' => 'Halo',
    ]);
    Sanctum::actingAs($buyer);

    $this->getJson('/api/v1/chats/'.$conversation->id.'/messages?after='.$existing->id)
        ->assertOk()
        ->assertJsonStructure(['data' => ['messages']]);
});

test('penjual dapat menandai produk terjual lewat chat', function () {
    ['seller' => $seller, 'product' => $product, 'conversation' => $conversation, 'buyer' => $buyer] = apiChatFixture();
    Order::factory()->create([
        'conversation_id' => $conversation->id,
        'buyer_id' => $buyer->id,
        'seller_id' => $seller->id,
        'product_id' => $product->id,
    ]);
    Sanctum::actingAs($seller);

    $this->postJson('/api/v1/chats/'.$conversation->id.'/sold', ['completion_type' => Order::COMPLETION_STOCK_OUT])
        ->assertOk();

    expect($product->refresh()->status)->toBe(Product::STATUS_TERJUAL);
});
