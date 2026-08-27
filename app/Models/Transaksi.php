<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTahunAnggaran;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaksi extends Model
{
    use HasFactory, BelongsToTahunAnggaran;

    protected $table = 'transaksi';

    protected $fillable = [
        'sekolah_id',
        'tahun_anggaran_id',
        'nomor_referensi_asal',
        'nomor_npb',
        'nomor_spb',
        'nomor_sppb',
        'tanggal_npb',
        'tanggal_spb',
        'tanggal_sppb',
        'pihak_peminta_id',
        'status',
    ];

    protected $casts = [
        'tanggal_npb' => 'date',
        'tanggal_spb' => 'date',
        'tanggal_sppb' => 'date',
    ];

    public function sekolah()
    {
        return $this->belongsTo(Sekolah::class);
    }

    public function pihakPeminta()
    {
        return $this->belongsTo(Pegawai::class, 'pihak_peminta_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransaksiItem::class);
    }
}