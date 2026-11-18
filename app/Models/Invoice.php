<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $table = 'invoice';

    public const MENUNGGU_PEMBAYARAN = 'menunggu_pembayaran';
    public const MENUNGGU_VERIFIKASI = 'menunggu_verifikasi';
    public const LUNAS = 'lunas';
    public const DITOLAK = 'ditolak';
    public const KEDALUWARSA = 'kedaluwarsa';
    public const DIBATALKAN = 'dibatalkan';

    /** Status "masih berjalan": triwulan di dalamnya dianggap sedang dipesan. */
    public const PENDING = [self::MENUNGGU_PEMBAYARAN, self::MENUNGGU_VERIFIKASI];

    // Keamanan: status, nominal, dan verifikasi TIDAK boleh diisi lewat mass-assignment dari input user.
    // Semuanya di-set eksplisit oleh InvoiceService.
    protected $guarded = ['id'];

    protected $casts = [
        'subtotal' => 'integer',
        'diskon' => 'integer',
        'total' => 'integer',
        'kode_unik' => 'integer',
        'total_bayar' => 'integer',
        'jatuh_tempo_at' => 'datetime',
        'dibayar_at' => 'datetime',
        'diverifikasi_at' => 'datetime',
    ];

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh');
    }

    public function sedangBerjalan(): bool
    {
        return in_array($this->status, self::PENDING, true);
    }

    /** Sekolah boleh upload bukti: belum dibayar & belum lewat jatuh tempo, atau sedang ditolak (upload ulang). */
    public function bisaUploadBukti(): bool
    {
        if ($this->status === self::DITOLAK) {
            return true;
        }

        return $this->status === self::MENUNGGU_PEMBAYARAN && $this->jatuh_tempo_at->isFuture();
    }

    public function bisaDibatalkan(): bool
    {
        return in_array($this->status, [self::MENUNGGU_PEMBAYARAN, self::DITOLAK], true);
    }

    /** Admin boleh menyetujui dari status apa pun kecuali yang sudah final (lunas/dibatalkan). */
    public function bisaDisetujui(): bool
    {
        return !in_array($this->status, [self::LUNAS, self::DIBATALKAN], true);
    }

    public function bisaDitolak(): bool
    {
        return in_array($this->status, [self::MENUNGGU_VERIFIKASI, self::MENUNGGU_PEMBAYARAN], true);
    }

    public function labelStatus(): string
    {
        return match ($this->status) {
            self::MENUNGGU_PEMBAYARAN => 'Menunggu pembayaran',
            self::MENUNGGU_VERIFIKASI => 'Menunggu verifikasi',
            self::LUNAS => 'Lunas',
            self::DITOLAK => 'Ditolak',
            self::KEDALUWARSA => 'Kedaluwarsa',
            self::DIBATALKAN => 'Dibatalkan',
            default => $this->status,
        };
    }

    /** Kelas Tailwind untuk badge status di halaman sekolah. */
    public function kelasBadge(): string
    {
        return match ($this->status) {
            self::LUNAS => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::MENUNGGU_VERIFIKASI => 'bg-sky-50 text-sky-700 border-sky-200',
            self::MENUNGGU_PEMBAYARAN => 'bg-amber-50 text-amber-700 border-amber-200',
            default => 'bg-zinc-100 text-zinc-600 border-zinc-200',
        };
    }
}
