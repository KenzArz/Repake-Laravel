<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\WithProductCover;
use App\Models\Review;
use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Review */
class ReviewResource extends JsonResource
{
    use WithProductCover;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'rating' => $this->rating,
            'komentar' => $this->komentar,
            'reviewer' => $this->whenLoaded('reviewer', fn () => [
                'id' => $this->reviewer->id,
                'name' => $this->reviewer->name,
                'avatar_url' => $this->reviewer->avatar_url ? MediaUrl::make($this->reviewer->avatar_url) : null,
            ]),
            'product' => $this->whenLoaded('product', fn () => $this->product ? [
                'id' => $this->product->id,
                'judul' => $this->product->judul,
                'harga' => $this->product->harga,
                'cover_image_url' => self::productCover($this->product),
            ] : null),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
