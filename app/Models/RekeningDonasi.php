<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RekeningDonasi extends Model
{
    protected $table = 'rekening_donasi';

    protected $fillable = [
        'nama_bank',
        'nomor_rekening',
        'atas_nama',
        'foto',
        'urutan',
    ];
}
