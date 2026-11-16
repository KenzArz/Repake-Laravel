<?php

use App\Models\Conversation;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function apiOrderFixture(): array
{
    $seller = User::factory()->create();
    $buyer = User::factory()->create();
    $product = Product::factory()->for($seller, 'seller')->create();
    $conversation = Conversation::factory()->create([
        'buyer_id' => $buyer->id,
        'seller_id' => $seller->id,
        'product_id' => $product->id,
    ]);
    $order = Order::factory()->create([
        'conversation_id' => $conversation->id,
        'buyer_id' => $buyer->id,
        'seller_id' => $seller->id,
        'product_id' => $product->id,
    ]);

    return compact('seller', 'buyer', 'product', 'conversation', 'order');
}

test('order menampilkan riwayat pembelian', function () {
    ['buyer' => $buyer, 'order' => $order] = apiOrderFixture();
    Sanctum::actingAs($buyer);

    $this->getJson('/api/v1/orders?role=pembelian')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', Order::STATUS_DISEPAKATI);
});

test('pembeli dapat membatalkan kesepakatan', function () {
    ['buyer' => $buyer, 'order' => $order] = apiOrderFixture();
    Sanctum::actingAs($buyer);

    $this->patchJson('/api/v1/orders/'.$order->id.'/status', ['status' => 'batal'])
        ->assertOk()
        ->assertJsonPath('data.status', Order::STATUS_BATAL);
});

test('penjual dapat menandai produk terjual', function () {
    ['seller' => $seller, 'order' => $order, 'product' => $product] = apiOrderFixture();
    Sanctum::actingAs($seller);

    $this->patchJson('/api/v1/orders/'.$order->id.'/status', ['status' => 'selesai'])
        ->assertOk()
        ->assertJsonPath('data.status', Order::STATUS_SELESAI);

    expect($product->refresh()->status)->toBe(Product::STATUS_TERJUAL);
});

test('pembeli tidak bisa menandai produk terjual', function () {
    ['buyer' => $buyer, 'order' => $order] = apiOrderFixture();
    Sanctum::actingAs($buyer);

    $this->patchJson('/api/v1/orders/'.$order->id.'/status', ['status' => 'selesai'])->assertStatus(403);
});

test('pihak luar tidak bisa mengubah order', function () {
    ['order' => $order] = apiOrderFixture();
    Sanctum::actingAs(User::factory()->create());

    $this->patchJson('/api/v1/orders/'.$order->id.'/status', ['status' => 'batal'])->assertStatus(403);
});

test('pembeli dapat menulis ulasan pada order', function () {
    ['buyer' => $buyer, 'order' => $order, 'product' => $product] = apiOrderFixture();
    Sanctum::actingAs($buyer);

    $this->postJson('/api/v1/orders/'.$order->id.'/reviews', [
        'rating' => 5,
        'komentar' => 'Bagus sekali.',
    ])->assertCreated()
        ->assertJsonPath('data.product.id', $product->id);

    expect(Review::where('order_id', $order->id)->where('reviewer_id', $buyer->id)->count())->toBe(1);
});

test('ulasan ganda untuk transaksi yang sama ditolak', function () {
    ['buyer' => $buyer, 'order' => $order] = apiOrderFixture();
    $order->update(['status' => Order::STATUS_DISEPAKATI]);
    Review::create([
        'order_id' => $order->id,
        'product_id' => $order->product_id,
        'reviewer_id' => $buyer->id,
        'reviewee_id' => $order->seller_id,
        'rating' => 5,
    ]);
    Sanctum::actingAs($buyer);

    $this->postJson('/api/v1/orders/'.$order->id.'/reviews', ['rating' => 4])->assertStatus(409);
});

test('penulis ulasan dapat memperbarui dan menghapus ulasannya', function () {
    ['buyer' => $buyer, 'order' => $order] = apiOrderFixture();
    $order->update(['status' => Order::STATUS_DISEPAKATI]);
    $review = Review::create([
        'order_id' => $order->id,
        'product_id' => $order->product_id,
        'reviewer_id' => $buyer->id,
        'reviewee_id' => $order->seller_id,
        'rating' => 5,
        'komentar' => 'Mantap',
    ]);
    Sanctum::actingAs($buyer);

    $this->patchJson('/api/v1/reviews/'.$review->id, ['rating' => 4, 'komentar' => 'Update.'])
        ->assertOk()
        ->assertJsonPath('data.rating', 4);

    $this->deleteJson('/api/v1/reviews/'.$review->id)->assertOk();

    expect(Review::find($review->id))->toBeNull();
});
