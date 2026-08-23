<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sekolah extends Model
{
    use HasFactory;

    protected $table = 'sekolah';

    protected $fillable = [
        'nama_sekolah',
        'kode_sekolah',
        'logo_sekolah',
        'logo_kabupaten',
        'kontak_wa',
        'email',
        'nama_pemerintah',
        'nama_dinas',
        'nama_korwil',
        'alamat',
        'tempat',
        'kode_klasifikasi_surat',
        'jabatan_resmi_sppb',
        'format_kode_npb',
        'format_kode_spb',
        'format_kode_sppb',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
