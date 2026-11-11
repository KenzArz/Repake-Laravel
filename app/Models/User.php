<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
        'whatsapp_number',
        'avatar',
        'city',
        'province',
        'role',
        'status',
        'firebase_uid',
        'username',
        'bio',
    ];

    /**
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Accessor & Mutator untuk kompatibilitas nama atribut lama: 'kota' -> 'city'
     */
    protected function kota(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value, array $attributes): ?string => $attributes['city'] ?? $value,
            set: fn (?string $value): array => ['city' => $value],
        );
    }

    /**
     * Accessor & Mutator untuk kompatibilitas nama atribut lama: 'phone' -> 'whatsapp_number'
     */
    protected function phone(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value, array $attributes): ?string => $attributes['whatsapp_number'] ?? $value,
            set: fn (?string $value): array => ['whatsapp_number' => $value],
        );
    }

    /**
     * Cek apakah user adalah admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Cek apakah status user aktif
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Relasi ke produk yang dijual user
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'user_id');
    }

    /**
     * Relasi ke record favorit yang dimiliki user
     */
    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class, 'user_id');
    }

    /**
     * Relasi ke percakapan chat sebagai pembeli
     */
    public function conversationsAsBuyer(): HasMany
    {
        return $this->hasMany(Conversation::class, 'buyer_id');
    }

    /**
     * Relasi ke percakapan chat sebagai penjual
     */
    public function conversationsAsSeller(): HasMany
    {
        return $this->hasMany(Conversation::class, 'seller_id');
    }

    /**
     * Relasi ke pesan yang dikirim oleh user
     */
    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    /**
     * Relasi ke laporan yang dibuat oleh user (sebagai pelapor)
     */
    public function reportsMade(): HasMany
    {
        return $this->hasMany(Report::class, 'reporter_id');
    }

    /**
     * Relasi ke laporan di mana user dilaporkan
     */
    public function reportsReceived(): HasMany
    {
        return $this->hasMany(Report::class, 'reported_user_id');
    }
}
