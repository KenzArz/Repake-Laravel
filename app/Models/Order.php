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

    /** Saat transaksi diselesaikan dan stok produk habis (produk ditandai terjual). */
    public const COMPLETION_STOCK_OUT = 'stok_habis';

    /** Saat transaksi diselesaikan tetapi produk masih tersedia (tetap dijual). */
    public const COMPLETION_STOCK_AVAILABLE = 'stok_tersedia';

    /** Order dibatalkan otomatis karena produk terjual ke pembeli lain. */
    public const CANCEL_REASON_SOLD_ELSEWHERE = 'sold_elsewhere';

    protected $fillable = [
        'conversation_id',
        'buyer_id',
        'seller_id',
        'product_id',
        'harga_final',
        'status',
        'completed_at',
        'completion_type',
        'cancelled_by',
        'cancel_reason',
    ];

    /**
     * Label status transaksi untuk tampilan (dipakai kartu order, resource API,
     * dan halaman riwayat agar konsisten di seluruh aplikasi).
     */
    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_DISEPAKATI => 'Menunggu COD',
            self::STATUS_SELESAI => 'Selesai',
            self::STATUS_BATAL => 'Dibatalkan',
            default => ucfirst((string) $this->status),
        };
    }

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
