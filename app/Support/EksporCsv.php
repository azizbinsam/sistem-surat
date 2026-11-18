<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Ekspor CSV sederhana untuk panel admin (tanpa antrean/queue).
 *  - BOM UTF-8 di awal file supaya Excel membaca huruf & tanda baca Indonesia dengan benar.
 *  - Perlindungan "CSV injection": sel yang diawali = + - @ (atau tab/enter) diberi tanda petik di depan,
 *    sehingga Excel tidak menjalankannya sebagai rumus (nama sekolah/user berasal dari input pengguna).
 */
class EksporCsv
{
    /**
     * @param  list<string>  $header
     * @param  iterable<list<scalar|null>>  $baris
     */
    public static function unduh(string $namaFile, array $header, iterable $baris): StreamedResponse
    {
        return response()->streamDownload(function () use ($header, $baris) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, array_map([self::class, 'bersihkan'], $header));

            foreach ($baris as $row) {
                fputcsv($out, array_map([self::class, 'bersihkan'], $row));
            }

            fclose($out);
        }, $namaFile, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public static function bersihkan(mixed $nilai): string
    {
        $teks = (string) ($nilai ?? '');

        if ($teks !== '' && in_array($teks[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $teks;
        }

        return $teks;
    }
}
