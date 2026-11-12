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

test('the same client id never creates a second message', function () {
    $payload = [
        'client_id' => 'aaaa-bbbb-cccc-dddd',
        'body' => 'Halo bang',
    ];

    $first = $this->actingAs($this->buyer)
        ->postJson(route('chat.messages.store', $this->conversation), $payload)
        ->assertOk()
        ->json('message.id');

    $second = $this->actingAs($this->buyer)
        ->postJson(route('chat.messages.store', $this->conversation), $payload)
        ->assertOk()
        ->json('message.id');

    expect($second)->toBe($first)
        ->and($this->conversation->messages()->count())->toBe(1);
});

test('sending a message returns ready to insert html', function () {
    $this->actingAs($this->buyer)
        ->postJson(route('chat.messages.store', $this->conversation), [
            'body' => 'Barangnya ready?',
        ])
        ->assertOk()
        ->assertJsonStructure(['html', 'message' => ['id', 'body'], 'receipts']);
});

test('polling marks incoming messages as delivered and read when visible', function () {
    $message = Message::factory()->create([
        'conversation_id' => $this->conversation->id,
        'sender_id' => $this->seller->id,
        'body' => 'Ready mas',
    ]);

    $this->actingAs($this->buyer)
        ->getJson(route('chat.messages.poll', ['conversation' => $this->conversation, 'after' => 0, 'visible' => 1]))
        ->assertOk()
        ->assertJsonPath('receipts.read_upto', 0);

    $message->refresh();

    expect($message->delivered_at)->not->toBeNull()
        ->and($message->read_at)->not->toBeNull();
});

test('the sender sees read receipts for their own messages', function () {
    $mine = Message::factory()->create([
        'conversation_id' => $this->conversation->id,
        'sender_id' => $this->buyer->id,
        'body' => 'Halo',
    ]);

    $this->actingAs($this->seller)
        ->getJson(route('chat.messages.poll', ['conversation' => $this->conversation, 'after' => 0]))
        ->assertOk();

    $this->actingAs($this->buyer)
        ->getJson(route('chat.messages.poll', ['conversation' => $this->conversation, 'after' => $mine->id]))
        ->assertOk()
        ->assertJsonPath('receipts.read_upto', $mine->id)
        ->assertJsonPath('receipts.delivered_upto', $mine->id);
});

test('opening the conversation marks incoming messages as read', function () {
    $message = Message::factory()->create([
        'conversation_id' => $this->conversation->id,
        'sender_id' => $this->seller->id,
    ]);

    $this->actingAs($this->buyer)
        ->get(route('chat.show', $this->conversation))
        ->assertOk();

    expect($message->refresh()->read_at)->not->toBeNull();
});

test('the unread endpoint counts only unread incoming messages', function () {
    Message::factory()->count(2)->create([
        'conversation_id' => $this->conversation->id,
        'sender_id' => $this->seller->id,
    ]);

    Message::factory()->create([
        'conversation_id' => $this->conversation->id,
        'sender_id' => $this->buyer->id,
    ]);

    $this->actingAs($this->buyer)
        ->getJson(route('chat.unread'))
        ->assertOk()
        ->assertJsonPath('total', 2);

    $this->actingAs($this->seller)
        ->getJson(route('chat.unread'))
        ->assertOk()
        ->assertJsonPath('total', 1);
});

test('the chat list can be filtered by buying and by unread', function () {
    Message::factory()->create([
        'conversation_id' => $this->conversation->id,
        'sender_id' => $this->seller->id,
    ]);

    $this->actingAs($this->buyer)
        ->get(route('chat.index', ['filter' => 'membeli']))
        ->assertOk()
        ->assertSee($this->seller->name);

    $this->actingAs($this->buyer)
        ->get(route('chat.index', ['filter' => 'menjual']))
        ->assertOk()
        ->assertDontSee($this->conversation->product->judul);

    $this->actingAs($this->buyer)
        ->get(route('chat.index', ['filter' => 'belum']))
        ->assertOk()
        ->assertSee($this->conversation->product->judul);
});

test('system messages never count as unread', function () {
    Message::factory()->create([
        'conversation_id' => $this->conversation->id,
        'sender_id' => $this->seller->id,
        'type' => Message::TYPE_SYSTEM,
        'body' => 'Penawaran disetujui',
    ]);

    $this->actingAs($this->buyer)
        ->getJson(route('chat.unread'))
        ->assertOk()
        ->assertJsonPath('total', 0);

    $this->actingAs($this->buyer)
        ->get(route('chat.index', ['filter' => 'belum']))
        ->assertOk()
        ->assertDontSee($this->conversation->product->judul);
});

test('accepting an offer posts a system message for both sides', function () {
    $offer = $this->conversation->messages()->create([
        'sender_id' => $this->buyer->id,
        'type' => Message::TYPE_OFFER,
        'offer_amount' => 600000,
        'offer_status' => Message::OFFER_PENDING,
    ]);

    $this->actingAs($this->seller)
        ->postJson(route('chat.offers.accept', [$this->conversation, $offer]))
        ->assertOk()
        ->assertJsonPath('message.offer_status', Message::OFFER_ACCEPTED)
        ->assertJsonPath('system.html', fn ($html) => str_contains($html, 'Penawaran disetujui'));

    expect($this->conversation->messages()->where('type', Message::TYPE_SYSTEM)->count())->toBe(1);
});

test('accepting the same offer twice creates only one order', function () {
    $offer = $this->conversation->messages()->create([
        'sender_id' => $this->buyer->id,
        'type' => Message::TYPE_OFFER,
        'offer_amount' => 600000,
        'offer_status' => Message::OFFER_PENDING,
    ]);

    $this->actingAs($this->seller)
        ->postJson(route('chat.offers.accept', [$this->conversation, $offer]))
        ->assertOk();

    $this->actingAs($this->seller)
        ->postJson(route('chat.offers.accept', [$this->conversation, $offer]))
        ->assertStatus(409);

    expect(Order::query()->count())->toBe(1)
        ->and($this->conversation->messages()->where('type', Message::TYPE_SYSTEM)->count())->toBe(1);
});

test('the product strip badge follows the latest offer status', function () {
    $offer = $this->conversation->messages()->create([
        'sender_id' => $this->buyer->id,
        'type' => Message::TYPE_OFFER,
        'offer_amount' => 600000,
        'offer_status' => Message::OFFER_PENDING,
    ]);

    $this->actingAs($this->buyer)
        ->get(route('chat.show', $this->conversation))
        ->assertOk()
        ->assertSee('Ditawar');

    $offer->update(['offer_status' => Message::OFFER_ACCEPTED]);

    $this->actingAs($this->buyer)
        ->get(route('chat.show', $this->conversation))
        ->assertOk()
        ->assertSee('Disetujui');
});

test('the chat page shows the online label of the counterpart', function () {
    $this->seller->forceFill(['last_seen_at' => now()])->save();

    $this->actingAs($this->buyer)
        ->get(route('chat.show', $this->conversation))
        ->assertOk()
        ->assertSee('Online')
        ->assertSee('data-online="1"', false);
});

test('the chat page renders the last-seen difference attributes when offline', function () {
    $this->seller->forceFill(['last_seen_at' => now()->subMinutes(12)])->save();

    $this->actingAs($this->buyer)
        ->get(route('chat.show', $this->conversation))
        ->assertOk()
        ->assertSee('data-online="0"', false)
        ->assertSee('data-last-seen-at="', false);
});
