<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\WithProductCover;
use App\Models\Product;
use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Product */
class ProductDetailResource extends JsonResource
{
    use WithProductCover;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'judul' => $this->judul,
            'slug' => $this->slug,
            'harga' => $this->harga,
            'harga_formatted' => 'Rp '.number_format((int) $this->harga, 0, ',', '.'),
            'kondisi' => $this->kondisi,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'deskripsi' => $this->deskripsi,
            'kota' => $this->kota,
            'patokan_lokasi' => $this->patokan_lokasi,
            'is_negotiable' => (bool) $this->is_negotiable,
            'is_favorite' => (bool) ($this->is_favorite ?? false),
            'favorites_count' => (int) ($this->favorites_count ?? 0),
            'cover_image_url' => self::productCover($this->resource),
            'category' => $this->whenLoaded('category', fn () => new CategoryResource($this->category)),
            'penjual' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'avatar_url' => $this->user->avatar_url ? MediaUrl::make($this->user->avatar_url) : null,
                'kota' => $this->user->kota,
                'is_online' => $this->user->isOnline(),
            ]),
            'seller_stats' => $this->when(isset($this->seller_stats), $this->seller_stats, [
                'rating' => 0.0,
                'reviews_count' => 0,
                'sold_count' => 0,
            ]),
            'images' => $this->whenLoaded('images', fn () => $this->images->map(fn ($img) => [
                'id' => $img->id,
                'url' => MediaUrl::make($img->path),
                'is_cover' => (bool) $img->is_cover,
            ])->values()),
            'similar' => $this->whenLoaded('similar', fn () => ProductResource::collection($this->similar) ?? []),
            'reviews' => $this->whenLoaded('reviews', fn () => ReviewResource::collection($this->reviews)),
            'viewer' => [
                'is_owner' => (bool) $this->viewer_is_owner,
                'can_review' => (bool) $this->viewer_can_review,
                'has_order' => (bool) $this->viewer_has_order,
            ],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
