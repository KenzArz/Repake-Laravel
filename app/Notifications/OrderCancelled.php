<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi pembatalan order.
 *
 * - `sold_elsewhere`  : order dibatalkan otomatis karena produk sudah terjual ke pembeli lain.
 * - lainnya / null    : pembatalan manual oleh pembeli atau penjual.
 */
class OrderCancelled extends Notification
{
    public function __construct(
        public string $productTitle,
        public ?int $conversationId,
        public ?string $reason = null,
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
        $rejected = $this->reason === Order::CANCEL_REASON_SOLD_ELSEWHERE;

        return [
            'kind' => 'order_cancelled',
            'title' => $rejected ? 'Produk terjual ke pembeli lain' : 'Transaksi dibatalkan',
            'body' => $rejected
                ? "Produk \"{$this->productTitle}\" sudah terjual ke pembeli lain, jadi kesepakatannya dibatalkan."
                : "Kesepakatan untuk produk \"{$this->productTitle}\" telah dibatalkan.",
            'url' => $this->conversationId
                ? route('chat.show', ['conversation' => $this->conversationId])
                : route('home'),
            'conversation_id' => $this->conversationId,
        ];
    }
}