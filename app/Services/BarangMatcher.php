<?php

namespace App\Services;

use App\Models\BarangAlias;
use App\Models\MasterBarang;

class BarangMatcher
{
    /**
     * Cari MasterBarang berdasarkan nama barang dari excel.
     * Urutan pencarian: exact match nama_barang → cek alias → null (belum dikenal).
     */
    public function cari(string $namaBarang, int $sekolahId): ?MasterBarang
    {
        $namaBersih = trim($namaBarang);

        $exact = MasterBarang::where('sekolah_id', $sekolahId)
            ->whereRaw('LOWER(nama_barang) = ?', [strtolower($namaBersih)])
            ->first();

        if ($exact) {
            return $exact;
        }

        $alias = BarangAlias::where('sekolah_id', $sekolahId)
            ->whereRaw('LOWER(nama_alias) = ?', [strtolower($namaBersih)])
            ->first();

        return $alias?->masterBarang;
    }

    /**
     * Simpan mapping baru sebagai alias, supaya upload berikutnya auto-match.
     */
    public function simpanAlias(string $namaBarang, ?string $spesifikasi, int $masterBarangId, int $sekolahId): void
    {
        BarangAlias::firstOrCreate([
            'sekolah_id' => $sekolahId,
            'master_barang_id' => $masterBarangId,
            'nama_alias' => trim($namaBarang),
        ], [
            'spesifikasi_alias' => $spesifikasi,
        ]);
    }
}
