<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaketLangganan extends Model
{
    protected $table = 'paket_langganan';

    protected $fillable = ['kode', 'nama', 'harga', 'jumlah_triwulan'];

    protected $casts = ['harga' => 'integer', 'jumlah_triwulan' => 'integer'];

    public static function harga(string $kode): int
    {
        return (int) static::where('kode', $kode)->value('harga');
    }
}
