<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'sekolah_id', 'paket_id', 'status',
        'tanggal_mulai', 'tanggal_berakhir',
        'fersaku_payment_id', 'dibuat_manual_oleh',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_berakhir' => 'date',
    ];

    public function sekolah()
    {
        return $this->belongsTo(Sekolah::class);
    }

    public function paket()
    {
        return $this->belongsTo(PaketSubscription::class, 'paket_id');
    }
}
