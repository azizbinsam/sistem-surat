<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use InvalidArgumentException;

/**
 * Helper triwulan (v2.0). Satu-satunya tempat yang tahu "triwulan itu bulan berapa sampai berapa".
 *
 * Dipakai bareng oleh:
 *  - filter tanggal/triwulan di halaman Penerimaan & Pengeluaran Barang (Fase 23)
 *  - pengunci generate surat per triwulan (Fase 24)
 *
 * Asumsi: Tahun Anggaran = Januari–Desember (TW1 Jan–Mar, TW2 Apr–Jun, TW3 Jul–Sep, TW4 Okt–Des).
 */
class Triwulan
{
    public const NAMA = [
        1 => 'Triwulan 1 (Jan–Mar)',
        2 => 'Triwulan 2 (Apr–Jun)',
        3 => 'Triwulan 3 (Jul–Sep)',
        4 => 'Triwulan 4 (Okt–Des)',
    ];

    /** Nomor triwulan (1–4) dari sebuah tanggal. Carbon sudah punya ->quarter. */
    public static function dariTanggal(CarbonInterface|string $tanggal): int
    {
        return Carbon::parse($tanggal)->quarter;
    }

    /**
     * Tanggal awal & akhir sebuah triwulan di tahun tertentu.
     *
     * @return array{0: Carbon, 1: Carbon} [awal 00:00:00, akhir 23:59:59]
     */
    public static function rentang(int $tahun, int $triwulan): array
    {
        if ($triwulan < 1 || $triwulan > 4) {
            throw new InvalidArgumentException("Triwulan harus 1–4, dapat: {$triwulan}");
        }

        $awal = Carbon::create($tahun, ($triwulan - 1) * 3 + 1, 1)->startOfDay();
        $akhir = $awal->copy()->addMonths(2)->endOfMonth();

        return [$awal, $akhir];
    }

    public static function nama(int $triwulan): string
    {
        return self::NAMA[$triwulan] ?? "Triwulan {$triwulan}";
    }
}
