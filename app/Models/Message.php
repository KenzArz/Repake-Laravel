<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'body',
        'image',
        'type',
        'offer_amount',
        'offer_status',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'offer_amount' => 'integer',
            'read_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
