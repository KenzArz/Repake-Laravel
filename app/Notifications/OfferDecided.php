<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Notifikasi keputusan tawaran (diterima/ditolak) untuk pembeli.
 *
 * Dikirim langsung (tanpa queue) supaya tetap tercatat di database
 * meski tidak ada queue worker yang berjalan.
 */
class OfferDecided extends Notification
{
    public function __construct(
        public bool $accepted,
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
        $status = $this->accepted ? 'Diterima ✅' : 'Ditolak ❌';

        return [
            'title' => "Tawaranmu {$status}",
            'body' => 'Tawaran Rp'.number_format($this->offerAmount, 0, ',', '.')." {$status} oleh penjual.",
            'url' => route('chat.show', ['conversation' => $this->conversationId]),
        ];
    }
}
