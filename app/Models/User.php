<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

#[Fillable(['name', 'email', 'password', 'firebase_uid', 'username', 'avatar', 'phone', 'kota', 'bio', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_MAHASISWA = 'mahasiswa';

    public const ROLE_ADMIN = 'admin';

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
        ];
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

            if (str_starts_with($avatar, 'http://') || str_starts_with($avatar, 'https://')) {
                return $avatar;
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
     * Produk yang difavoritkan, lewat tabel favorites.
     */
    public function favoritedProducts(): HasManyThrough
    {
        return $this->hasManyThrough(
            Product::class,
            Favorite::class,
            'user_id',
            'id',
            'id',
            'product_id',
        );
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
        return $this->hasMany(Review::class, 'seller_id');
    }

    public function reviewsGiven(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }
}
