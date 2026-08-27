<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTahunAnggaran;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pegawai extends Model
{
    use HasFactory, SoftDeletes, BelongsToTahunAnggaran;

    protected $table = 'pegawai';

    protected $fillable = [
        'sekolah_id',
        'tahun_anggaran_id',
        'nama',
        'nip',
        'jabatan',
        'kategori',
        'ttd_path',
    ];

    public function sekolah()
    {
        return $this->belongsTo(Sekolah::class);
    }
}
