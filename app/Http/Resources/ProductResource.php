<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\WithProductCover;
use App\Models\Product;
use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Product */
class ProductResource extends JsonResource
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
            'kota' => $this->kota,
            'patokan_lokasi' => $this->patokan_lokasi,
            'is_negotiable' => (bool) $this->is_negotiable,
            'is_favorite' => (bool) ($this->is_favorite ?? false),
            'jarak_km' => $this->when(isset($this->distance_km), $this->distance_km ?? null, null),
            'cover_image_url' => self::productCover($this->resource),
            'category' => $this->whenLoaded('category', fn () => new CategoryResource($this->category)),
            'penjual' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'avatar_url' => $this->user->avatar_url ? MediaUrl::make($this->user->avatar_url) : null,
            ]),
            'favorites_count' => $this->whenCounted('favorites'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
