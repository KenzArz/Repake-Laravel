<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\WithProductCover;
use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Conversation */
class ConversationResource extends JsonResource
{
    use WithProductCover;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $other = $request->user() ? $this->counterpartFor($request->user()->id) : null;

        return [
            'id' => $this->id,
            'buyer' => $this->whenLoaded('buyer', fn () => new UserResource($this->buyer)),
            'seller' => $this->whenLoaded('seller', fn () => new UserResource($this->seller)),
            'other_user' => $other ? new UserResource($other) : null,
            'product' => $this->whenLoaded('product', fn () => $this->product ? [
                'id' => $this->product->id,
                'judul' => $this->product->judul,
                'slug' => $this->product->slug,
                'harga' => $this->product->harga,
                'status' => $this->product->status,
                'status_label' => $this->product->status_label,
                'cover_image_url' => self::productCover($this->product),
            ] : null),
            'last_message' => $this->whenLoaded('lastMessage', fn () => new MessageResource($this->lastMessage)),
            'latest_offer' => $this->whenLoaded('latestOffer', fn () => $this->latestOffer ? new MessageResource($this->latestOffer) : null),
            'unread_count' => (int) ($this->unread_count ?? 0),
            'order' => $this->whenLoaded('order', fn () => $this->order ? new OrderResource($this->order) : null),
            'last_message_at' => $this->last_message_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
