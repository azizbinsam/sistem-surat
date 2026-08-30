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
        'npsn',
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

    /**
     * Setiap sekolah baru otomatis dapat 1 tahun anggaran default yang aktif (PRD §12.3).
     * Ini juga yang bikin test lama (yang langsung Sekolah::create() tanpa lewat alur
     * onboarding) tetap jalan tanpa perlu diubah satu-satu.
     */
    protected static function booted(): void
    {
        static::created(function (self $sekolah) {
            if (!$sekolah->tahunAnggaran()->exists()) {
                $sekolah->tahunAnggaran()->create([
                    'tahun' => now()->year,
                    'nomor_urut_terakhir' => 0,
                    'status' => 'aktif',
                ]);
            }
        });
    }

    public function tahunAnggaran(): HasMany
    {
        return $this->hasMany(TahunAnggaran::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
