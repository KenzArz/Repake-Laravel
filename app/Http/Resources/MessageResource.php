<?php

namespace App\Http\Resources;

use App\Models\Message;
use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Message */
class MessageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'sender_id' => $this->sender_id,
            'sender' => $this->whenLoaded('sender', fn () => [
                'id' => $this->sender->id,
                'name' => $this->sender->name,
                'avatar_url' => $this->sender->avatar_url ? MediaUrl::make($this->sender->avatar_url) : null,
            ]),
            'type' => $this->type,
            'body' => $this->body,
            'offer_amount' => $this->offer_amount,
            'offer_status' => $this->offer_status,
            'receipt_status' => $this->receiptStatus(),
            'read_at' => $this->read_at?->toIso8601String(),
            'delivered_at' => $this->delivered_at?->toIso8601String(),
            'image_urls' => $this->relationLoaded('images')
                ? collect($this->imagePaths())->map(fn (string $p) => MediaUrl::make($p))->values()->all()
                : ($this->image ? [MediaUrl::make($this->image)] : []),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
