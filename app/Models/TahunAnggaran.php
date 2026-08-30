<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TahunAnggaran extends Model
{
    use HasFactory;

    protected $table = 'tahun_anggaran';

    protected $fillable = [
        'sekolah_id',
        'tahun',
        'nomor_urut_terakhir',
        'status',
    ];

    public function sekolah()
    {
        return $this->belongsTo(Sekolah::class);
    }
}