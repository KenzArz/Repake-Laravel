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
    $this->order = Order::factory()->create([
        'seller_id' => $this->seller->id,
        'buyer_id' => $this->buyer->id,
        'product_id' => $this->product->id,
        'status' => Order::STATUS_DISEPAKATI,
    ]);
});

test('a buyer can review the seller for the product once the offer is agreed', function () {
    $this->actingAs($this->buyer)
        ->post(route('reviews.store', $this->order), [
            'rating' => 5,
            'komentar' => 'Barang bagus, penjual ramah.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $review = Review::sole();
    expect($review->order_id)->toBe($this->order->id)
        ->and($review->product_id)->toBe($this->product->id)
        ->and($review->reviewer_id)->toBe($this->buyer->id)
        ->and($review->reviewee_id)->toBe($this->seller->id)
        ->and($review->rating)->toBe(5)
        ->and($review->komentar)->toBe('Barang bagus, penjual ramah.');
});

test('a seller can review the buyer without a product', function () {
    $this->actingAs($this->seller)
        ->post(route('reviews.store', $this->order), [
            'rating' => 4,
            'komentar' => 'Pembeli responsif dan ramah.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $review = Review::sole();
    expect($review->product_id)->toBeNull()
        ->and($review->reviewer_id)->toBe($this->seller->id)
        ->and($review->reviewee_id)->toBe($this->buyer->id)
        ->and($review->rating)->toBe(4);
});

test('both parties can review the same order', function () {
    $this->actingAs($this->buyer)
        ->post(route('reviews.store', $this->order), ['rating' => 5]);

    $this->actingAs($this->seller)
        ->post(route('reviews.store', $this->order), ['rating' => 4]);

    expect(Review::count())->toBe(2)
        ->and(Review::where('reviewee_id', $this->seller->id)->count())->toBe(1)
        ->and(Review::where('reviewee_id', $this->buyer->id)->count())->toBe(1);
});

test('a non-participant cannot review the order', function () {
    $outsider = User::factory()->create();

    $this->actingAs($outsider)
        ->post(route('reviews.store', $this->order), ['rating' => 4])
        ->assertForbidden();

    expect(Review::count())->toBe(0);
});

test('a cancelled order cannot be reviewed', function () {
    $this->order->update(['status' => Order::STATUS_BATAL]);

    $this->actingAs($this->buyer)
        ->post(route('reviews.store', $this->order), ['rating' => 4])
        ->assertForbidden();

    expect(Review::count())->toBe(0);
});

test('a user can only review the same order once but the counterpart still can', function () {
    Review::create([
        'order_id' => $this->order->id,
        'product_id' => $this->product->id,
        'reviewer_id' => $this->buyer->id,
        'reviewee_id' => $this->seller->id,
        'rating' => 3,
        'komentar' => 'Kurang bagus.',
    ]);

    $this->actingAs($this->buyer)
        ->post(route('reviews.store', $this->order), ['rating' => 5])
        ->assertStatus(409);

    $this->actingAs($this->seller)
        ->post(route('reviews.store', $this->order), ['rating' => 5])
        ->assertRedirect();

    expect(Review::count())->toBe(2);
});

test('rating and komentar are validated', function () {
    $this->actingAs($this->buyer)->post(route('reviews.store', $this->order), [])
        ->assertSessionHasErrors(['rating']);

    $this->actingAs($this->buyer)->post(route('reviews.store', $this->order), ['rating' => 9])
        ->assertSessionHasErrors(['rating']);

    $this->actingAs($this->buyer)->post(route('reviews.store', $this->order), [
        'rating' => 3,
        'komentar' => str_repeat('a', 501),
    ])->assertSessionHasErrors(['komentar']);

    expect(Review::count())->toBe(0);
});

test('only the review author can update their review', function () {
    $review = Review::create([
        'order_id' => $this->order->id,
        'product_id' => $this->product->id,
        'reviewer_id' => $this->buyer->id,
        'reviewee_id' => $this->seller->id,
        'rating' => 3,
        'komentar' => 'Awalnya kurang puas.',
    ]);

    $outsider = User::factory()->create();
    $this->actingAs($outsider)
        ->patch(route('reviews.update', $review), ['rating' => 5])
        ->assertForbidden();

    $this->actingAs($this->buyer)
        ->patch(route('reviews.update', $review), [
            'rating' => 5,
            'komentar' => 'Ternyata puas setelah dipakai.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($review->fresh()->rating)->toBe(5)
        ->and($review->fresh()->komentar)->toBe('Ternyata puas setelah dipakai.');
});

test('only the review author can delete their review', function () {
    $review = Review::create([
        'order_id' => $this->order->id,
        'product_id' => $this->product->id,
        'reviewer_id' => $this->buyer->id,
        'reviewee_id' => $this->seller->id,
        'rating' => 4,
        'komentar' => 'Boleh hapus.',
    ]);

    $outsider = User::factory()->create();
    $this->actingAs($outsider)->delete(route('reviews.destroy', $review))->assertForbidden();
    expect(Review::count())->toBe(1);

    $this->actingAs($this->buyer)->delete(route('reviews.destroy', $review))->assertRedirect();
    expect(Review::count())->toBe(0);
});

test('a product review is scoped to its product while a user review is not', function () {
    $otherProduct = Product::factory()->create(['user_id' => $this->seller->id]);

    Review::create([
        'order_id' => $this->order->id,
        'product_id' => $this->product->id,
        'reviewer_id' => $this->buyer->id,
        'reviewee_id' => $this->seller->id,
        'rating' => 5,
        'komentar' => 'ULASAN PRODUK INI.',
    ]);
    Review::create([
        'order_id' => $this->order->id,
        'product_id' => null,
        'reviewer_id' => $this->seller->id,
        'reviewee_id' => $this->buyer->id,
        'rating' => 4,
        'komentar' => 'ULASAN ORANG INI.',
    ]);

    $this->get($this->product->detailUrl())
        ->assertOk()
        ->assertSee('ULASAN PRODUK INI.')
        ->assertDontSee('ULASAN ORANG INI.');

    // Produk lain tidak menampilkan ulasan produk pertama.
    $this->get($otherProduct->detailUrl())
        ->assertOk()
        ->assertDontSee('ULASAN PRODUK INI.');
});

test('product detail page renders the review form for an agreed order', function () {
    $this->get($this->product->detailUrl())->assertOk()
        ->assertSee('Ulasan Produk')
        ->assertDontSee('Bagaimana produk ini menurut Anda?');

    // Pembeli dengan order disepakati → form tampil.
    $this->actingAs($this->buyer)->get($this->product->detailUrl())
        ->assertOk()
        ->assertSee('Bagaimana produk ini menurut Anda?');
});

test('seller sees a review form on the buyer profile after an agreed order', function () {
    $url = $this->buyer->profileUrl();
    $url .= (str_contains($url, '?') ? '&' : '?').'tab=ulasan';

    $this->actingAs($this->seller)
        ->get($url)
        ->assertOk()
        ->assertSee('Beri ulasan untuk');

    // Setelah mengulas, form hilang.
    Review::create([
        'order_id' => $this->order->id,
        'product_id' => null,
        'reviewer_id' => $this->seller->id,
        'reviewee_id' => $this->buyer->id,
        'rating' => 5,
        'komentar' => 'Pembeli oke.',
    ]);

    $this->actingAs($this->seller)
        ->get($url)
        ->assertOk()
        ->assertDontSee('Beri ulasan untuk')
        ->assertSee('Pembeli oke.');
});

test('profile rating is the combined average of every review received', function () {
    // U sebagai penjual menerima ulasan produk (5).
    $buyerB = User::factory()->create();
    $productU = Product::factory()->create(['user_id' => $this->seller->id]);
    $orderAsSeller = Order::factory()->selesai()->create([
        'seller_id' => $this->seller->id,
        'buyer_id' => $buyerB->id,
        'product_id' => $productU->id,
    ]);
    Review::create([
        'order_id' => $orderAsSeller->id,
        'product_id' => $productU->id,
        'reviewer_id' => $buyerB->id,
        'reviewee_id' => $this->seller->id,
        'rating' => 5,
    ]);

    // U juga sebagai pembeli menerima ulasan orang (3) dari penjual lain.
    $otherSeller = User::factory()->create();
    $productOther = Product::factory()->create(['user_id' => $otherSeller->id]);
    $orderAsBuyer = Order::factory()->selesai()->create([
        'seller_id' => $otherSeller->id,
        'buyer_id' => $this->seller->id,
        'product_id' => $productOther->id,
    ]);
    Review::create([
        'order_id' => $orderAsBuyer->id,
        'product_id' => null,
        'reviewer_id' => $otherSeller->id,
        'reviewee_id' => $this->seller->id,
        'rating' => 3,
    ]);

    // (5 + 3) / 2 = 4.0 pada profil.
    $this->get($this->seller->profileUrl())
        ->assertOk()
        ->assertSee('4.0');
});
