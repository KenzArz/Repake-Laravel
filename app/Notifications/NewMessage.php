<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi pesan chat baru untuk lawan bicara.
 */
class NewMessage extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $senderName,
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
            'title' => "Pesan baru dari {$this->senderName}",
            'body' => "{$this->senderName} mengirim pesan untukmu.",
            'url' => route('chat.show', ['conversation' => $this->conversationId]),
        ];
    }
}
