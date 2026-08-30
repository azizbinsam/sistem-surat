<?php

namespace App\Models\Concerns;

use App\Models\TahunAnggaran;
use App\Services\TahunAnggaranResolver;
use Illuminate\Database\Eloquent\Builder;

/**
 * Ditempel ke model yang datanya harus terpisah per Tahun Anggaran (PRD §12.3):
 * MasterBarang, Pegawai, BarangMasuk, Transaksi, KoreksiStok.
 *
 * Dua efek otomatis, TANPA perlu ubah query manual di tiap halaman Livewire:
 * 1. Global scope: query otomatis kefilter ke tahun anggaran yang lagi aktif/dipilih user.
 * 2. creating(): kalau tahun_anggaran_id belum diisi manual, otomatis diisi dari tahun
 *    anggaran aktif milik sekolah yang match `sekolah_id` yang lagi di-create.
 *
 * Kalau nggak ada user login (dipanggil dari test/seeder/console tanpa auth), scope
 * di-skip (nggak ada konteks buat resolve "aktif yang mana") — fallback creating()
 * tetap jalan asal ada baris `status = aktif` buat sekolah itu.
 */
trait BelongsToTahunAnggaran
{
    public static function bootBelongsToTahunAnggaran(): void
    {
        static::addGlobalScope('tahunAnggaran', function (Builder $builder) {
            if (!auth()->check() || !auth()->user()->sekolah_id) {
                return;
            }

            $tahunAnggaran = app(TahunAnggaranResolver::class)->aktif(auth()->user()->sekolah);

            if ($tahunAnggaran) {
                $builder->where($builder->getModel()->getTable() . '.tahun_anggaran_id', $tahunAnggaran->id);
            }
        });

        static::creating(function ($model) {
            if ($model->tahun_anggaran_id || !$model->sekolah_id) {
                return;
            }

            if (auth()->check() && auth()->user()->sekolah_id === $model->sekolah_id) {
                $tahunAnggaran = app(TahunAnggaranResolver::class)->aktif(auth()->user()->sekolah);
            } else {
                // Konteks tanpa auth (test/seeder) — ambil langsung status=aktif buat sekolah itu.
                $tahunAnggaran = TahunAnggaran::where('sekolah_id', $model->sekolah_id)
                    ->where('status', 'aktif')
                    ->latest('tahun')
                    ->first();
            }

            if ($tahunAnggaran) {
                $model->tahun_anggaran_id = $tahunAnggaran->id;
            }
        });
    }

    public function tahunAnggaran()
    {
        return $this->belongsTo(TahunAnggaran::class);
    }
}