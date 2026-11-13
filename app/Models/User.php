<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

#[Fillable(['name', 'email', 'password', 'firebase_uid', 'username', 'avatar', 'phone', 'kota', 'bio', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUuids, Notifiable;

    public const ROLE_MAHASISWA = 'mahasiswa';

    public const ROLE_ADMIN = 'admin';

    /**
     * Admin ditentukan dari kolom role di database, bukan dari allowlist environment.
     * Untuk menambah atau mencabut akses admin, ubah langsung di tabel users.
     */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_banned' => 'boolean',
            'last_seen_at' => 'datetime',
        ];
    }

    /**
     * URL profil publik penjual. Username dipakai lebih dulu agar URL rapi,
     * dengan UUID sebagai identitas kanonis lewat query `id`.
     *
     * Akun lama yang belum punya username memakai UUID sebagai segmen path,
     * sehingga profilnya tetap unik dan tidak bisa ditebak dari nomor urut.
     */
    public function profileUrl(): string
    {
        return route('sellers.show', array_filter([
            'seller' => $this->username ?: $this->id,
            'id' => $this->username ? $this->id : null,
        ]));
    }

    /**
     * Dianggap online bila heartbeat terakhir < 5 menit lalu.
     */
    public function isOnline(int $minutes = 5): bool
    {
        return $this->last_seen_at !== null
            && $this->last_seen_at->gte(now()->subMinutes($minutes));
    }

    /**
     * URL avatar yang aman dipakai di tag <img> — mendukung URL absolut
     * (dari Google/Firebase) maupun path relatif di disk publik.
     */
    protected function avatarUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            $avatar = $this->avatar;

            if (blank($avatar)) {
                return null;
            }

            if (str_starts_with($avatar, 'http://') || str_starts_with($avatar, 'https://') || str_starts_with($avatar, '/')) {
                return $avatar;
            }

            if (file_exists(public_path($avatar))) {
                return asset($avatar);
            }

            return Storage::url($avatar);
        });
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'user_id');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    /**
     * Produk yang difavoritkan, lewat tabel pivot favorites.
     */
    public function favoritedProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'favorites')->withTimestamps();
    }

    public function conversationsAsBuyer(): HasMany
    {
        return $this->hasMany(Conversation::class, 'buyer_id');
    }

    public function conversationsAsSeller(): HasMany
    {
        return $this->hasMany(Conversation::class, 'seller_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'reporter_id');
    }

    public function ordersAsBuyer(): HasMany
    {
        return $this->hasMany(Order::class, 'buyer_id');
    }

    public function ordersAsSeller(): HasMany
    {
        return $this->hasMany(Order::class, 'seller_id');
    }

    public function reviewsReceived(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewee_id');
    }

    public function reviewsGiven(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }
}
