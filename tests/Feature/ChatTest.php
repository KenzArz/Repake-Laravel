<?php

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Product;
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

    $message = $this->conversation->messages()->sole();

    expect($message->image)->not->toBeNull()
        ->and(Storage::disk('public')->exists($message->image))->toBeTrue();
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
