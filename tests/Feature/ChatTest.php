<?php

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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

test('guest is redirected when opening the chat list', function () {
    $this->get(route('chat.index'))->assertRedirect(route('home'));
});

test('a non-participant gets 404 when opening a conversation', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('chat.show', $this->conversation))->assertNotFound();
});

test('a participant opens the conversation with its messages', function () {
    $message = Message::factory()->create([
        'conversation_id' => $this->conversation->id,
        'sender_id' => $this->seller->id,
        'body' => 'Ready mas?',
    ]);

    $this->actingAs($this->buyer)
        ->get(route('chat.show', $this->conversation))
        ->assertOk()
        ->assertSee('Ready mas?');

    expect($message->exists)->toBeTrue();
});

test('the chat shows the review action and no complete button once the offer is agreed', function () {
    Order::factory()->create([
        'conversation_id' => $this->conversation->id,
        'seller_id' => $this->seller->id,
        'buyer_id' => $this->buyer->id,
        'product_id' => $this->product->id,
        'status' => Order::STATUS_DISEPAKATI,
    ]);

    // Pembeli melihat tombol ulasan, bukan "Selesaikan Transaksi".
    $this->actingAs($this->buyer)
        ->get(route('chat.show', $this->conversation))
        ->assertOk()
        ->assertSee('Tinggalkan ulasan')
        ->assertDontSee('Selesaikan Transaksi')
        ->assertDontSee('Tandai Terjual');

    // Penjual melihat "Tandai Terjual".
    $this->actingAs($this->seller)
        ->get(route('chat.show', $this->conversation))
        ->assertOk()
        ->assertSee('Tandai Terjual');
});

test('the review action turns into edit ulasan after the buyer reviews', function () {
    $order = Order::factory()->create([
        'conversation_id' => $this->conversation->id,
        'seller_id' => $this->seller->id,
        'buyer_id' => $this->buyer->id,
        'product_id' => $this->product->id,
        'status' => Order::STATUS_DISEPAKATI,
    ]);

    Review::create([
        'order_id' => $order->id,
        'product_id' => $this->product->id,
        'reviewer_id' => $this->buyer->id,
        'reviewee_id' => $this->seller->id,
        'rating' => 5,
    ]);

    $this->actingAs($this->buyer)
        ->get(route('chat.show', $this->conversation))
        ->assertOk()
        ->assertSee('Edit ulasan')
        ->assertDontSee('Tinggalkan ulasan');
});

test('a buyer starts a conversation from a product page', function () {
    $this->actingAs($this->buyer);

    $response = $this->post(route('chat.store'), ['product_id' => $this->product->id]);

    $conversation = Conversation::query()
        ->where('buyer_id', $this->buyer->id)
        ->where('seller_id', $this->seller->id)
        ->where('product_id', $this->product->id)
        ->sole();

    $response->assertRedirect(route('chat.show', $conversation));
});

test('starting a conversation twice reuses the existing one', function () {
    $this->actingAs($this->buyer);

    $this->post(route('chat.store'), ['product_id' => $this->product->id]);
    $this->post(route('chat.store'), ['product_id' => $this->product->id]);

    expect(Conversation::count())->toBe(1);
});

test('a seller cannot start a chat on their own product', function () {
    $this->actingAs($this->seller);

    $this->post(route('chat.store'), ['product_id' => $this->product->id])
        ->assertForbidden();

    // beforeEach sudah membuat 1 percakapan pembeli x penjual; tidak boleh bertambah.
    expect(Conversation::count())->toBe(1);
});

test('a participant sends a text message and last activity updates', function () {
    $this->conversation->update(['last_message_at' => now()->subMinute()]);
    $before = $this->conversation->last_message_at;

    $this->actingAs($this->buyer)
        ->postJson(route('chat.messages.store', $this->conversation), ['body' => 'Halo, masih ada?'])
        ->assertOk()
        ->assertJsonPath('message.body', 'Halo, masih ada?')
        ->assertJsonPath('message.sender_id', $this->buyer->id);

    $this->conversation->refresh();

    expect($this->conversation->messages()->where('body', 'Halo, masih ada?')->exists())->toBeTrue()
        ->and($this->conversation->last_message_at)->toBeGreaterThan($before);
});

test('a message needs a body or an image', function () {
    $this->actingAs($this->buyer);

    $this->post(route('chat.messages.store', $this->conversation), [])
        ->assertSessionHasErrors(['body']);
});

test('a participant sends an image message', function () {
    Storage::fake('public');
    // PNG 1x1 valid agar rule `image` lolos tanpa ekstensi GD.
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

    $this->actingAs($this->seller)
        ->postJson(route('chat.messages.store', $this->conversation), [
            'image' => UploadedFile::fake()->createWithContent('foto.png', $png),
        ])
        ->assertOk()
        ->assertJsonPath('message.body', null);

    $message = $this->conversation->messages()->with('images')->sole();

    expect($message->image)->not->toBeNull()
        ->and(Storage::disk('public')->exists($message->image))->toBeTrue()
        ->and($message->images)->toHaveCount(1)
        ->and($message->images->first()->path)->toBe($message->image);
});

test('polling returns only messages newer than the given id', function () {
    $first = Message::factory()->create([
        'conversation_id' => $this->conversation->id,
        'sender_id' => $this->seller->id,
        'body' => 'pesan pertama',
    ]);
    Message::factory()->create([
        'conversation_id' => $this->conversation->id,
        'sender_id' => $this->buyer->id,
        'body' => 'pesan kedua',
    ]);

    $this->actingAs($this->buyer)
        ->getJson(route('chat.messages.poll', ['conversation' => $this->conversation, 'after' => $first->id]))
        ->assertOk()
        ->assertJsonCount(1, 'messages')
        ->assertJsonPath('messages.0.body', 'pesan kedua');
});

test('a non-participant gets 404 when polling', function () {
    $this->actingAs(User::factory()->create());

    $this->getJson(route('chat.messages.poll', $this->conversation))->assertNotFound();
});

test('the sender deletes their own message', function () {
    $message = Message::factory()->create([
        'conversation_id' => $this->conversation->id,
        'sender_id' => $this->buyer->id,
        'body' => 'typo',
    ]);

    $this->actingAs($this->buyer)
        ->delete(route('chat.messages.destroy', $message))
        ->assertRedirect();

    expect($this->conversation->messages()->count())->toBe(0);
});

test('a non-sender cannot delete a message', function () {
    $message = Message::factory()->create([
        'conversation_id' => $this->conversation->id,
        'sender_id' => $this->buyer->id,
        'body' => 'pesan pembeli',
    ]);

    $this->actingAs($this->seller)
        ->delete(route('chat.messages.destroy', $message))
        ->assertForbidden();

    expect($message->refresh()->exists)->toBeTrue();
});

test('deleting an image message removes the stored file', function () {
    Storage::fake('public');
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

    $this->actingAs($this->seller)
        ->postJson(route('chat.messages.store', $this->conversation), [
            'image' => UploadedFile::fake()->createWithContent('foto.png', $png),
        ]);

    $message = $this->conversation->messages()->sole();
    $path = $message->image;

    $this->actingAs($this->seller)->delete(route('chat.messages.destroy', $message));

    expect(Storage::disk('public')->exists($path))->toBeFalse();
});

test('several photos are sent as a single message', function () {
    Storage::fake('public');
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

    $this->actingAs($this->seller)
        ->postJson(route('chat.messages.store', $this->conversation), [
            'body' => 'Foto produknya ya kak',
            'images' => [
                UploadedFile::fake()->createWithContent('satu.png', $png),
                UploadedFile::fake()->createWithContent('dua.png', $png),
                UploadedFile::fake()->createWithContent('tiga.png', $png),
            ],
        ])
        ->assertOk()
        ->assertJsonPath('message.body', 'Foto produknya ya kak');

    $message = $this->conversation->messages()->with('images')->sole();

    expect($this->conversation->messages()->count())->toBe(1)
        ->and($message->images)->toHaveCount(3)
        ->and($message->image)->toBeNull()
        ->and($message->images->pluck('urutan')->all())->toBe([0, 1, 2])
        ->and($message->images->every(
            fn ($image) => Storage::disk('public')->exists($image->path),
        ))->toBeTrue();
});

test('more than four photos render as a four tile grid with the remaining count', function () {
    Storage::fake('public');
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

    $files = collect(range(1, 6))
        ->map(fn ($index) => UploadedFile::fake()->createWithContent("foto-{$index}.png", $png))
        ->all();

    $payload = $this->actingAs($this->seller)
        ->postJson(route('chat.messages.store', $this->conversation), ['images' => $files])
        ->assertOk()
        ->json();

    $html = $payload['html'];

    expect(substr_count($html, 'data-lightbox-index'))->toBe(4)
        ->and($html)->toContain('+2')
        ->and($this->conversation->messages()->sole()->images()->count())->toBe(6);
});

test('a batch of photos is limited to twenty files', function () {
    Storage::fake('public');
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

    $files = collect(range(1, 21))
        ->map(fn ($index) => UploadedFile::fake()->createWithContent("foto-{$index}.png", $png))
        ->all();

    $this->actingAs($this->seller)
        ->postJson(route('chat.messages.store', $this->conversation), ['images' => $files])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['images']);
});

test('non image attachments are rejected', function () {
    Storage::fake('public');

    $this->actingAs($this->seller)
        ->postJson(route('chat.messages.store', $this->conversation), [
            'images' => [UploadedFile::fake()->createWithContent('catatan.txt', 'bukan gambar')],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['images.0']);
});

test('deleting a photo message removes every stored file', function () {
    Storage::fake('public');
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

    $this->actingAs($this->seller)
        ->postJson(route('chat.messages.store', $this->conversation), [
            'images' => [
                UploadedFile::fake()->createWithContent('satu.png', $png),
                UploadedFile::fake()->createWithContent('dua.png', $png),
            ],
        ]);

    $message = $this->conversation->messages()->with('images')->sole();
    $paths = $message->images->pluck('path')->all();

    expect($paths)->toHaveCount(2);

    $this->actingAs($this->seller)->delete(route('chat.messages.destroy', $message));

    foreach ($paths as $path) {
        expect(Storage::disk('public')->exists($path))->toBeFalse();
    }
});

test('polling new photo messages includes their grid markup', function () {
    Storage::fake('public');
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

    $this->actingAs($this->seller)
        ->postJson(route('chat.messages.store', $this->conversation), [
            'images' => [
                UploadedFile::fake()->createWithContent('satu.png', $png),
                UploadedFile::fake()->createWithContent('dua.png', $png),
            ],
        ])
        ->assertOk();

    $this->actingAs($this->buyer)
        ->getJson(route('chat.messages.poll', ['conversation' => $this->conversation, 'after' => 0]))
        ->assertOk()
        ->assertJsonCount(1, 'messages')
        ->assertJsonPath('messages.0.html', fn ($html) => str_contains($html, 'data-lightbox-group'));
});
