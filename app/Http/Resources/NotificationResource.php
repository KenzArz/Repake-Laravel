<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource notifikasi database Laravel.
 *
 * __construct mengurai payload `data` (array JSON) menjadi atribut tambahan
 * yang dibaca toArray, agar struktur tetap konsisten.
 */
class NotificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $payload = $this->data ?? [];

        return [
            'id' => $this->id,
            'type' => $this->type,
            'kind' => $payload['kind'] ?? null,
            'title' => $payload['title'] ?? null,
            'body' => $payload['body'] ?? null,
            'conversation_id' => $payload['conversation_id'] ?? null,
            'read_at' => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
