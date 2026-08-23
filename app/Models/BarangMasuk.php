<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BarangMasuk extends Model
{
    use HasFactory;

    protected $table = 'barang_masuk';

    protected $fillable = [
        'sekolah_id',
        'nomor_bpu',
        'tanggal',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function sekolah()
    {
        return $this->belongsTo(Sekolah::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BarangMasukItem::class);
    }
}
