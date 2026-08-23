<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaketSubscription extends Model
{
    use HasFactory;

    protected $table = 'paket_subscription';

    protected $fillable = ['nama_paket', 'harga', 'durasi_hari'];
}
