<?php

use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seller = User::factory()->create();
    $this->buyer = User::factory()->create();
    $this->product = Product::factory()->create(['user_id' => $this->seller->id]);
});

test('a buyer with a completed order can review that product', function () {
    $order = Order::factory()->selesai()->create([
        'seller_id' => $this->seller->id,
        'buyer_id' => $this->buyer->id,
        'product_id' => $this->product->id,
    ]);

    $this->actingAs($this->buyer)
        ->post(route('reviews.store', $this->product), [
            'rating' => 5,
            'komentar' => 'Barang bagus, penjual ramah.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $review = Review::sole();
    expect($review->order_id)->toBe($order->id)
        ->and($review->product_id)->toBe($this->product->id)
        ->and($review->reviewer_id)->toBe($this->buyer->id)
        ->and($review->seller_id)->toBe($this->seller->id)
        ->and($review->rating)->toBe(5)
        ->and($review->komentar)->toBe('Barang bagus, penjual ramah.');
});

test('a buyer without a completed order cannot review', function () {
    // Order ada tapi belum selesai.
    Order::factory()->create([
        'seller_id' => $this->seller->id,
        'buyer_id' => $this->buyer->id,
        'product_id' => $this->product->id,
    ]);

    $this->actingAs($this->buyer)
        ->post(route('reviews.store', $this->product), ['rating' => 4])
        ->assertForbidden();

    expect(Review::count())->toBe(0);
});

test('a buyer cannot review a product they did not buy', function () {
    $this->actingAs($this->buyer)
        ->post(route('reviews.store', $this->product), ['rating' => 4])
        ->assertForbidden();

    expect(Review::count())->toBe(0);
});

test('the product owner cannot review their own product', function () {
    Order::factory()->selesai()->create([
        'seller_id' => $this->seller->id,
        'buyer_id' => $this->buyer->id,
        'product_id' => $this->product->id,
    ]);

    $this->actingAs($this->seller)
        ->post(route('reviews.store', $this->product), ['rating' => 5])
        ->assertForbidden();

    expect(Review::count())->toBe(0);
});

test('a buyer can only review a product once per order', function () {
    $order = Order::factory()->selesai()->create([
        'seller_id' => $this->seller->id,
        'buyer_id' => $this->buyer->id,
        'product_id' => $this->product->id,
    ]);
    Review::create([
        'order_id' => $order->id,
        'product_id' => $this->product->id,
        'reviewer_id' => $this->buyer->id,
        'seller_id' => $this->seller->id,
        'rating' => 3,
        'komentar' => 'Kurang bagus.',
    ]);

    $this->actingAs($this->buyer)
        ->post(route('reviews.store', $this->product), ['rating' => 5])
        ->assertStatus(409);

    expect(Review::count())->toBe(1);
});

test('rating and komentar are validated', function () {
    Order::factory()->selesai()->create([
        'seller_id' => $this->seller->id,
        'buyer_id' => $this->buyer->id,
        'product_id' => $this->product->id,
    ]);

    $this->actingAs($this->buyer)->post(route('reviews.store', $this->product), [])
        ->assertSessionHasErrors(['rating']);

    $this->actingAs($this->buyer)->post(route('reviews.store', $this->product), ['rating' => 9])
        ->assertSessionHasErrors(['rating']);

    $this->actingAs($this->buyer)->post(route('reviews.store', $this->product), [
        'rating' => 3,
        'komentar' => str_repeat('a', 501),
    ])->assertSessionHasErrors(['komentar']);

    expect(Review::count())->toBe(0);
});

test('only the review author can update their review', function () {
    $order = Order::factory()->selesai()->create([
        'seller_id' => $this->seller->id,
        'buyer_id' => $this->buyer->id,
        'product_id' => $this->product->id,
    ]);
    $review = Review::create([
        'order_id' => $order->id,
        'product_id' => $this->product->id,
        'reviewer_id' => $this->buyer->id,
        'seller_id' => $this->seller->id,
        'rating' => 3,
        'komentar' => 'Awalnya kurang puas.',
    ]);

    $otherBuyer = User::factory()->create();
    $this->actingAs($otherBuyer)
        ->patch(route('reviews.update', [$this->product, $review]), ['rating' => 5])
        ->assertForbidden();

    $this->actingAs($this->buyer)
        ->patch(route('reviews.update', [$this->product, $review]), [
            'rating' => 5,
            'komentar' => 'Ternyata puas setelah dipakai.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($review->fresh()->rating)->toBe(5)
        ->and($review->fresh()->komentar)->toBe('Ternyata puas setelah dipakai.');
});

test('only the review author can delete their review', function () {
    $order = Order::factory()->selesai()->create([
        'seller_id' => $this->seller->id,
        'buyer_id' => $this->buyer->id,
        'product_id' => $this->product->id,
    ]);
    $review = Review::create([
        'order_id' => $order->id,
        'product_id' => $this->product->id,
        'reviewer_id' => $this->buyer->id,
        'seller_id' => $this->seller->id,
        'rating' => 4,
        'komentar' => 'Boleh hapus.',
    ]);

    $otherBuyer = User::factory()->create();
    $this->actingAs($otherBuyer)->delete(route('reviews.destroy', [$this->product, $review]))
        ->assertForbidden();
    expect(Review::count())->toBe(1);

    $this->actingAs($this->buyer)->delete(route('reviews.destroy', [$this->product, $review]))
        ->assertRedirect();
    expect(Review::count())->toBe(0);
});

test('product reviews are scoped to that product only', function () {
    $otherProduct = Product::factory()->create(['user_id' => $this->seller->id]);

    $order = Order::factory()->selesai()->create([
        'seller_id' => $this->seller->id,
        'buyer_id' => $this->buyer->id,
        'product_id' => $this->product->id,
    ]);
    Review::create([
        'order_id' => $order->id,
        'product_id' => $this->product->id,
        'reviewer_id' => $this->buyer->id,
        'seller_id' => $this->seller->id,
        'rating' => 5,
        'komentar' => 'Ulasan unik untuk produk ini.',
    ]);

    $this->get($this->product->detailUrl())
        ->assertOk()
        ->assertSee('Ulasan unik untuk produk ini.');

    // Produk lain tidak menampilkan review produk pertama.
    $this->get($otherProduct->detailUrl())
        ->assertOk()
        ->assertDontSee('Ulasan unik untuk produk ini.');

    expect(Review::count())->toBe(1);
});

test('product detail page renders the review form only for an eligible buyer', function () {
    $this->get($this->product->detailUrl())->assertOk()
        ->assertSee('Ulasan Produk')
        ->assertDontSee('Bagaimana produk ini menurut Anda?');

    // Pembeli berkunjung tapi belum selesai transaksi → form sembunyi.
    $this->actingAs($this->buyer)->get($this->product->detailUrl())
        ->assertOk()
        ->assertDontSee('Bagaimana produk ini menurut Anda?');

    // Pembeli dengan order selesai → form tampil.
    Order::factory()->selesai()->create([
        'seller_id' => $this->seller->id,
        'buyer_id' => $this->buyer->id,
        'product_id' => $this->product->id,
    ]);

    $this->actingAs($this->buyer)->get($this->product->detailUrl())
        ->assertOk()
        ->assertSee('Bagaimana produk ini menurut Anda?');
});

test('a product review contributes to the seller aggregate rating', function () {
    $orderOne = Order::factory()->selesai()->create([
        'seller_id' => $this->seller->id,
        'buyer_id' => $this->buyer->id,
        'product_id' => $this->product->id,
    ]);
    Review::create([
        'order_id' => $orderOne->id,
        'product_id' => $this->product->id,
        'reviewer_id' => $this->buyer->id,
        'seller_id' => $this->seller->id,
        'rating' => 5,
        'komentar' => 'Luar biasa.',
    ]);

    $secondBuyer = User::factory()->create();
    $orderTwo = Order::factory()->selesai()->create([
        'seller_id' => $this->seller->id,
        'buyer_id' => $secondBuyer->id,
        'product_id' => $this->product->id,
    ]);
    Review::create([
        'order_id' => $orderTwo->id,
        'product_id' => $this->product->id,
        'reviewer_id' => $secondBuyer->id,
        'seller_id' => $this->seller->id,
        'rating' => 3,
        'komentar' => 'Kurang memuaskan.',
    ]);

    // Rerata (5 + 3) / 2 = 4.0 → tampil pada rating penjual di detail produk.
    $this->get($this->product->detailUrl())
        ->assertOk()
        ->assertSee('4.0');
});
