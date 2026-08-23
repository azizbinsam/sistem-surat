<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BarangMasukItem extends Model
{
    use HasFactory;

    protected $table = 'barang_masuk_item';

    protected $fillable = [
        'barang_masuk_id',
        'master_barang_id',
        'spesifikasi',
        'satuan',
        'jumlah',
    ];

    public function barangMasuk()
    {
        return $this->belongsTo(BarangMasuk::class);
    }

    public function masterBarang()
    {
        return $this->belongsTo(MasterBarang::class);
    }
}
