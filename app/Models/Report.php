<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_REVIEWED = 'reviewed';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_RESOLVED = 'resolved';

    public const REASON_PENIPUAN = 'penipuan';

    public const REASON_BARANG_PALSU = 'barang_palsu';

    public const REASON_KONTEN_TIDAK_PANTAS = 'konten_tidak_pantas';

    public const REASON_HARGA_TIDAK_WAJAR = 'harga_tidak_wajar';

    public const REASON_LAINNYA = 'lainnya';

    protected $fillable = [
        'product_id',
        'reporter_id',
        'alasan',
        'keterangan',
        'bukti',
        'status',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
