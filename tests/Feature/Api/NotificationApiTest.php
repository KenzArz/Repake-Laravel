<?php

use App\Models\User;
use App\Notifications\NewMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('daftar notifikasi menampilkan data notifikasi', function () {
    $user = User::factory()->create();
    $user->notify(new NewMessage('Aldi', 5));
    $user->notify(new NewMessage('Budi', 6));
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/notifications');

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.kind', 'new_message')
        ->assertJsonStructure(['data' => ['0' => ['id', 'title', 'created_at']]]);

    $conversationIds = collect($response->json('data'))->pluck('conversation_id')->all();
    expect($conversationIds)->toContain(5)->toContain(6);
});

test('jumlah notifikasi belum dibaca', function () {
    $user = User::factory()->create();
    $user->notify(new NewMessage('Aldi', 5));
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/notifications/unread-count')
        ->assertOk()
        ->assertJsonPath('data.unread_count', 1);
});

test('semua notifikasi dapat ditandai dibaca', function () {
    $user = User::factory()->create();
    $user->notify(new NewMessage('Aldi', 5));
    $user->notify(new NewMessage('Budi', 6));
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/notifications/read-all')->assertOk();

    expect($user->refresh()->unreadNotifications()->count())->toBe(0);
});
