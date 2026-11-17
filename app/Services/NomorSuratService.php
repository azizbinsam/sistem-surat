<?php

namespace App\Services;

use App\Models\Sekolah;
use App\Models\TahunAnggaran;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class NomorSuratService
{
    protected array $romawi = [
        1 => 'I',
        2 => 'II',
        3 => 'III',
        4 => 'IV',
        5 => 'V',
        6 => 'VI',
        7 => 'VII',
        8 => 'VIII',
        9 => 'IX',
        10 => 'X',
        11 => 'XI',
        12 => 'XII',
    ];

    /**
     * Generate nomor NPB baru (increment per Tahun Anggaran sejak v1.1 — nomor urut
     * reset tiap tahun anggaran baru, TIDAK PERNAH dipakai ulang dalam 1 tahun anggaran).
     */
    public function generateNomorNpb(Sekolah $sekolah, TahunAnggaran $tahunAnggaran, Carbon $tanggal): string
    {
        // lockForUpdate = "kunci" baris tahun_anggaran ini di database sampai transaksi selesai.
        // Kalau dua proses generate jalan hampir bersamaan, yang kedua MENUNGGU yang pertama
        // selesai dulu — jadi mustahil dua surat dapat nomor urut yang sama (sebelumnya
        // increment lalu refresh bisa "kesalip" proses lain di antara keduanya).
        // Kalau dipanggil dari dalam DB::transaction milik pemanggil (misal bulk generate),
        // kunci ditahan sampai transaksi terluar itu selesai.
        return DB::transaction(function () use ($sekolah, $tahunAnggaran, $tanggal) {
            TahunAnggaran::whereKey($tahunAnggaran->getKey())->lockForUpdate()->firstOrFail();

            $tahunAnggaran->increment('nomor_urut_terakhir');
            $tahunAnggaran->refresh();

            return $this->formatNpb($sekolah, $tanggal, (int) $tahunAnggaran->nomor_urut_terakhir);
        });
    }

    /**
     * Susun nomor NPB dari nomor urut yang sudah ditentukan (TANPA nyentuh/naikin counter
     * nomor_urut_terakhir) — dipakai buat import data historis dari Excel yang cuma
     * ngisi angka urutnya aja (misal "0012"), bukan nomor lengkap (PRD §8.1).
     */
    public function formatNpb(Sekolah $sekolah, Carbon $tanggal, int $urut): string
    {
        $urutFormatted = str_pad((string) $urut, 4, '0', STR_PAD_LEFT);
        $bulanRomawi = $this->romawi[$tanggal->month];

        return "{$sekolah->kode_klasifikasi_surat}/{$urutFormatted}/NPB-{$sekolah->kode_sekolah}/{$bulanRomawi}/{$tanggal->year}";
    }

    /**
     * Turunan nomor SPB dari nomor NPB (nomor urut + .1, prefix jenis surat berubah).
     */
    public function turunanSpb(string $nomorNpb): string
    {
        return $this->turunan($nomorNpb, '.1', 'SPB-');
    }

    /**
     * Turunan nomor SPPB dari nomor NPB (nomor urut + .2, prefix jenis surat berubah).
     */
    public function turunanSppb(string $nomorNpb): string
    {
        return $this->turunan($nomorNpb, '.2', 'SPPB-');
    }

    protected function turunan(string $nomorNpb, string $suffix, string $prefixBaru): string
    {
        // format: {klasifikasi}/{urut}/NPB-{kode_sekolah}/{bulan_romawi}/{tahun}
        $parts = explode('/', $nomorNpb);
        $parts[1] .= $suffix;
        $parts[2] = preg_replace('/^NPB-/', $prefixBaru, $parts[2]);

        return implode('/', $parts);
    }
}