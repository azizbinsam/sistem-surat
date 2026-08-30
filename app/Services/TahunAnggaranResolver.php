<?php

namespace App\Services;

use App\Models\Sekolah;
use App\Models\TahunAnggaran;

/**
 * Tunggal sumber kebenaran buat "tahun anggaran mana yang lagi aktif/dipilih" (PRD §12.3-12.4).
 *
 * Session cuma nyimpen ID pilihan user (biar bisa pindah-pindah tanpa reload penuh);
 * fallback-nya selalu baris `status = aktif` milik sekolah itu kalau session kosong
 * atau nunjuk ke tahun anggaran yang bukan miliknya (proteksi tenant).
 */
class TahunAnggaranResolver
{
    /**
     * $sekolah nullable dengan sengaja — beberapa caller (dashboard, topbar) bisa aja
     * kepanggil buat user yang belum lengkapi profil (belum punya sekolah_id) sebelum
     * middleware sempat redirect. Daripada tiap caller guard manual sendiri-sendiri
     * (rawan kelewat), null di-terima di sini dan langsung balikin null.
     */
    public function aktif(?Sekolah $sekolah): ?TahunAnggaran
    {
        if (!$sekolah) {
            return null;
        }

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
            ->where('status', 'aktif')
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