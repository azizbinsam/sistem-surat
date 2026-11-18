<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HakTriwulan extends Model
{
    protected $table = 'hak_triwulan';

    protected $fillable = [
        'sekolah_id',
        'tahun_anggaran_id',
        'triwulan',
        'sumber',
        'invoice_item_id',
        'berlaku_sampai',
        'status',
        'catatan',
    ];

    protected $casts = [
        'triwulan' => 'integer',
        'berlaku_sampai' => 'date',
    ];

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class);
    }

    public function tahunAnggaran(): BelongsTo
    {
        return $this->belongsTo(TahunAnggaran::class);
    }

    public function invoiceItem(): BelongsTo
    {
        return $this->belongsTo(InvoiceItem::class);
    }

    /** Hak yang masih berlaku: status aktif DAN (tanpa batas ATAU batasnya belum lewat). */
    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('status', 'aktif')
            ->where(fn($q) => $q->whereNull('berlaku_sampai')->orWhere('berlaku_sampai', '>=', now()->toDateString()));
    }
}
