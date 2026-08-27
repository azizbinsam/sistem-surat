<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTahunAnggaran;

class KoreksiStok extends Model
{
    use HasFactory, BelongsToTahunAnggaran;

    protected $table = 'koreksi_stok';

    protected $fillable = [
        'sekolah_id',
        'tahun_anggaran_id',
        'master_barang_id',
        'tanggal',
        'jumlah',
        'alasan',
        'user_id',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function sekolah()
    {
        return $this->belongsTo(Sekolah::class);
    }

    public function masterBarang()
    {
        return $this->belongsTo(MasterBarang::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
