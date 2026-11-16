<?php

namespace App\Http\Resources;

use App\Models\User;
use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'username' => $this->username,
            'avatar_url' => $this->avatar_url ? MediaUrl::make($this->avatar_url) : null,
            'kota' => $this->kota,
            'bio' => $this->bio,
            'phone' => $this->phone,
            'role' => $this->role,
            'is_admin' => $this->isAdmin(),
            'is_banned' => (bool) $this->is_banned,
            'is_online' => $this->isOnline(),
            'last_seen_at' => $this->last_seen_at?->toIso8601String(),
        ];
    }
}
