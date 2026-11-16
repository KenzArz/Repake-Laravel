<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\WithProductCover;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Order */
class OrderResource extends JsonResource
{
    use WithProductCover;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'status' => $this->status,
            'status_label' => $this->statusLabel(),
            'completion_type' => $this->completion_type,
            'cancelled_by' => $this->cancelled_by,
            'cancel_reason' => $this->cancel_reason,
            'harga_final' => $this->harga_final,
            'harga_final_formatted' => 'Rp '.number_format((int) $this->harga_final, 0, ',', '.'),
            'has_reviewed' => (bool) ($this->has_reviewed ?? false),
            'buyer' => $this->whenLoaded('buyer', fn () => [
                'id' => $this->buyer->id,
                'name' => $this->buyer->name,
                'avatar_url' => $this->buyer->avatar_url,
            ]),
            'seller' => $this->whenLoaded('seller', fn () => [
                'id' => $this->seller->id,
                'name' => $this->seller->name,
                'avatar_url' => $this->seller->avatar_url,
            ]),
            'product' => $this->whenLoaded('product', fn () => $this->product ? [
                'id' => $this->product->id,
                'judul' => $this->product->judul,
                'slug' => $this->product->slug,
                'harga' => $this->product->harga,
                'status' => $this->product->status,
                'cover_image_url' => self::productCover($this->product),
            ] : null),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
