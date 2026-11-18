<?php

namespace App\Support;

/**
 * Angka → kata dalam bahasa Indonesia, untuk kwitansi.
 * Contoh: 39017 → "Tiga puluh sembilan ribu tujuh belas rupiah".
 */
class Terbilang
{
    protected const SATUAN = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];

    public static function rupiah(int $angka): string
    {
        if ($angka < 0) {
            return 'Minus ' . lcfirst(self::rupiah(abs($angka)));
        }

        return $angka === 0 ? 'Nol rupiah' : ucfirst(self::angka($angka)) . ' rupiah';
    }

    public static function angka(int $n): string
    {
        // Setiap rentang dipecah jadi "bagian besar + sisa", lalu sisanya diterjemahkan dengan fungsi yang sama (rekursif).
        // Kata "se-" (seratus, seribu) dipakai khusus untuk kelipatan 1 di ratusan dan ribuan.
        return match (true) {
            $n < 12 => self::SATUAN[$n],
            $n < 20 => self::angka($n - 10) . ' belas',
            $n < 100 => self::angka(intdiv($n, 10)) . ' puluh' . self::sisa($n % 10),
            $n < 200 => 'seratus' . self::sisa($n - 100),
            $n < 1000 => self::angka(intdiv($n, 100)) . ' ratus' . self::sisa($n % 100),
            $n < 2000 => 'seribu' . self::sisa($n - 1000),
            $n < 1_000_000 => self::angka(intdiv($n, 1000)) . ' ribu' . self::sisa($n % 1000),
            $n < 1_000_000_000 => self::angka(intdiv($n, 1_000_000)) . ' juta' . self::sisa($n % 1_000_000),
            $n < 1_000_000_000_000 => self::angka(intdiv($n, 1_000_000_000)) . ' miliar' . self::sisa($n % 1_000_000_000),
            default => self::angka(intdiv($n, 1_000_000_000_000)) . ' triliun' . self::sisa($n % 1_000_000_000_000),
        };
    }

    protected static function sisa(int $n): string
    {
        return $n > 0 ? ' ' . self::angka($n) : '';
    }
}
