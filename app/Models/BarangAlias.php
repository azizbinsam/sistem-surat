<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BarangAlias extends Model
{
    use HasFactory;

    protected $table = 'barang_alias';

    protected $fillable = [
        'sekolah_id',
        'master_barang_id',
        'nama_alias',
        'spesifikasi_alias',
    ];

    public function masterBarang()
    {
        return $this->belongsTo(MasterBarang::class);
    }

    public function sekolah()
    {
        return $this->belongsTo(Sekolah::class);
    }
}
