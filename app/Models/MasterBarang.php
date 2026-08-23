<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterBarang extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'master_barang';

    protected $fillable = [
        'sekolah_id',
        'kode_barang',
        'nama_barang',
        'kategori',
        'satuan_default',
        'keperluan_default',
    ];

    public function sekolah()
    {
        return $this->belongsTo(Sekolah::class);
    }
}
