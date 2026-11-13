<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Notifikasi pembatalan order karena produk sudah terjual ke pembeli lain.
 */
class OrderCancelled extends Notification
{
    public function __construct(
        public string $productTitle,
        public ?int $conversationId,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Transaksi dibatalkan',
            'body' => "Produk \"{$this->productTitle}\" sudah terjual ke pembeli lain.",
            'url' => $this->conversationId
                ? route('chat.show', ['conversation' => $this->conversationId])
                : route('home'),
        ];
    }
}
