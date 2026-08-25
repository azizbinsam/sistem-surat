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

    /**
     * Cari spesifikasi dari BPU terakhir buat kode barang ini (yang tanggalnya <= tanggal transaksi,
     * biar konsisten kronologis kayak logic ledger). Dipakai sebagai fallback kalau sekolah nggak
     * isi spesifikasi manual di transaksi keluar - data aslinya udah ada di riwayat penerimaan.
     */
    public function cariSpesifikasiTerakhir(int $masterBarangId, ?\Carbon\Carbon $sebelumTanggal = null): ?string
    {
        $item = \App\Models\BarangMasukItem::where('master_barang_id', $masterBarangId)
            ->whereHas('barangMasuk', function ($q) use ($sebelumTanggal) {
                if ($sebelumTanggal) {
                    $q->where('tanggal', '<=', $sebelumTanggal->toDateString());
                }
            })
            ->with('barangMasuk')
            ->get()
            ->sortByDesc(fn($i) => $i->barangMasuk->tanggal)
            ->first();

        return $item?->spesifikasi;
    }
}
