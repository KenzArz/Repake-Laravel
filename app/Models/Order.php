<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_DISEPAKATI = 'disepakati';

    public const STATUS_SELESAI = 'selesai';

    public const STATUS_BATAL = 'batal';

    protected $fillable = [
        'conversation_id',
        'buyer_id',
        'seller_id',
        'product_id',
        'harga_final',
        'status',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'harga_final' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Ulasan pada transaksi ini (maksimal satu dari pembeli & satu dari penjual).
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Ulasan yang ditulis user tertentu pada transaksi ini.
     */
    public function reviewBy(string $userId): ?Review
    {
        return $this->reviews()->where('reviewer_id', $userId)->first();
    }
}
