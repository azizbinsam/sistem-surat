<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pegawai extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pegawai';

    protected $fillable = [
        'sekolah_id',
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
