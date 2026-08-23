<?php

namespace App\Services;

use App\Models\Sekolah;
use Carbon\Carbon;

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
     * Generate nomor NPB baru (increment global, nomor urut TIDAK PERNAH dipakai ulang).
     */
    public function generateNomorNpb(Sekolah $sekolah, Carbon $tanggal): string
    {
        $sekolah->increment('nomor_urut_terakhir');
        $sekolah->refresh();

        $urut = str_pad((string) $sekolah->nomor_urut_terakhir, 4, '0', STR_PAD_LEFT);
        $bulanRomawi = $this->romawi[$tanggal->month];

        return "{$sekolah->kode_klasifikasi_surat}/{$urut}/NPB-{$sekolah->kode_sekolah}/{$bulanRomawi}/{$tanggal->year}";
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
