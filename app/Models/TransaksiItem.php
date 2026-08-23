<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransaksiItem extends Model
{
    use HasFactory;

    protected $table = 'transaksi_item';

    protected $fillable = [
        'transaksi_id',
        'master_barang_id',
        'spesifikasi',
        'jumlah',
        'satuan',
        'keperluan',
    ];

    public function transaksi()
    {
        return $this->belongsTo(Transaksi::class);
    }

    public function masterBarang()
    {
        return $this->belongsTo(MasterBarang::class);
    }
}
