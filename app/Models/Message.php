<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    use HasFactory;

    public const TYPE_TEXT = 'text';

    public const TYPE_OFFER = 'offer';

    public const TYPE_SYSTEM = 'system';

    public const OFFER_PENDING = 'pending';

    public const OFFER_ACCEPTED = 'accepted';

    public const OFFER_REJECTED = 'rejected';

    public const OFFER_CANCELLED = 'cancelled';

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'client_id',
        'body',
        'image',
        'type',
        'offer_amount',
        'offer_status',
        'read_at',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'offer_amount' => 'integer',
            'read_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    /**
     * Status centang untuk pengirim: terkirim / diterima / dibaca.
     */
    public function receiptStatus(): string
    {
        return match (true) {
            $this->read_at !== null => 'read',
            $this->delivered_at !== null => 'delivered',
            default => 'sent',
        };
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Semua gambar pada satu pesan, sesuai urutan pilih pengirim.
     */
    public function images(): HasMany
    {
        return $this->hasMany(MessageImage::class)->orderBy('urutan')->orderBy('id');
    }

    /**
     * Path gambar pesan. Kolom `image` lama tetap dipakai sebagai cadangan
     * untuk data yang belum sempat dibackfill.
     *
     * @return array<int, string>
     */
    public function imagePaths(): array
    {
        $paths = $this->images->pluck('path')->filter()->values()->all();

        if ($paths === [] && $this->image !== null) {
            return [$this->image];
        }

        return $paths;
    }

    public function hasImage(): bool
    {
        return $this->imagePaths() !== [];
    }
}
