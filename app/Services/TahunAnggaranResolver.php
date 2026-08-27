<?php

namespace App\Services;

use App\Models\Sekolah;
use App\Models\TahunAnggaran;

/**
 * Tunggal sumber kebenaran buat "tahun anggaran mana yang lagi aktif/dipilih" (PRD §12.3-12.4).
 *
 * Session cuma nyimpen ID pilihan user (biar bisa pindah-pindah tanpa reload penuh);
 * fallback-nya selalu baris `is_aktif = true` milik sekolah itu kalau session kosong
 * atau nunjuk ke tahun anggaran yang bukan miliknya (proteksi tenant).
 */
class TahunAnggaranResolver
{
    public function aktif(Sekolah $sekolah): ?TahunAnggaran
    {
        $sessionId = session('tahun_anggaran_id');

        if ($sessionId) {
            $dariSesi = TahunAnggaran::where('id', $sessionId)
                ->where('sekolah_id', $sekolah->id)
                ->first();

            if ($dariSesi) {
                return $dariSesi;
            }
        }

        return TahunAnggaran::where('sekolah_id', $sekolah->id)
            ->where('is_aktif', true)
            ->latest('tahun')
            ->first();
    }

    public function pilih(TahunAnggaran $tahunAnggaran, Sekolah $sekolah): void
    {
        if ($tahunAnggaran->sekolah_id !== $sekolah->id) {
            abort(403);
        }

        session(['tahun_anggaran_id' => $tahunAnggaran->id]);
    }
}
