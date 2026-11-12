<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

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

    /**
     * Label alasan pelaporan untuk tampilan admin.
     */
    public const ALASAN_LABELS = [
        self::REASON_PENIPUAN => 'Indikasi Penipuan / Scam',
        self::REASON_BARANG_PALSU => 'Barang Tiruan / Palsu',
        self::REASON_KONTEN_TIDAK_PANTAS => 'Konten Tidak Pantas',
        self::REASON_HARGA_TIDAK_WAJAR => 'Harga Tidak Wajar / Spam',
        self::REASON_LAINNYA => 'Alasan Lainnya',
    ];

    /**
     * Label status laporan untuk tampilan admin.
     */
    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'Menunggu',
        self::STATUS_REVIEWED => 'Ditinjau',
        self::STATUS_REJECTED => 'Ditolak',
        self::STATUS_RESOLVED => 'Selesai',
    ];

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

    /**
     * Label alasan yang ramah dibaca.
     */
    protected function alasanLabel(): Attribute
    {
        return Attribute::get(
            fn (): string => self::ALASAN_LABELS[$this->alasan] ?? ucfirst(str_replace('_', ' ', (string) $this->alasan))
        );
    }

    /**
     * Label status yang ramah dibaca.
     */
    protected function statusLabel(): Attribute
    {
        return Attribute::get(
            fn (): string => self::STATUS_LABELS[$this->status] ?? ucfirst((string) $this->status)
        );
    }

    /**
     * URL publik bukti laporan (disimpan di disk public/reports).
     */
    protected function buktiUrl(): Attribute
    {
        return Attribute::get(
            fn (): ?string => filled($this->bukti) ? Storage::url($this->bukti) : null
        );
    }
}
