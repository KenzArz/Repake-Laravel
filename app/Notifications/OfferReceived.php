<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi tawaran harga masuk untuk penjual.
 */
class OfferReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $buyerName,
        public int $offerAmount,
        public int $conversationId,
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
            'title' => "Tawaran dari {$this->buyerName}",
            'body' => "{$this->buyerName} menawar Rp".number_format($this->offerAmount, 0, ',', '.').'.',
            'url' => route('chat.show', ['conversation' => $this->conversationId]),
        ];
    }
}
