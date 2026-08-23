<?php

namespace App\Services;

use App\Models\BarangMasukItem;
use App\Models\KoreksiStok;
use App\Models\Transaksi;
use App\Models\TransaksiItem;

class PersediaanService
{
    public function totalMasuk(int $masterBarangId): int
    {
        return BarangMasukItem::where('master_barang_id', $masterBarangId)->sum('jumlah');
    }

    public function totalKoreksi(int $masterBarangId): int
    {
        return KoreksiStok::where('master_barang_id', $masterBarangId)->sum('jumlah');
    }

    /**
     * Total barang keluar. Kalau $sebelumTransaksi diisi, cuma hitung transaksi
     * yang terjadi SEBELUM transaksi itu (urut kronologis by tanggal_npb, lalu id sebagai tie-breaker).
     */
    public function totalKeluar(int $masterBarangId, ?Transaksi $sebelumTransaksi = null): int
    {
        return TransaksiItem::where('master_barang_id', $masterBarangId)
            ->whereHas('transaksi', function ($q) use ($sebelumTransaksi) {
                if ($sebelumTransaksi) {
                    $q->where(function ($qq) use ($sebelumTransaksi) {
                        $qq->where('tanggal_npb', '<', $sebelumTransaksi->tanggal_npb)
                            ->orWhere(function ($qqq) use ($sebelumTransaksi) {
                                $qqq->where('tanggal_npb', $sebelumTransaksi->tanggal_npb)
                                    ->where('id', '<', $sebelumTransaksi->id);
                            });
                    });
                }
            })
            ->sum('jumlah');
    }

    /**
     * Sisa stok saat ini (akumulasi penuh sampai sekarang). Dipakai di halaman ringkasan.
     */
    public function sisaSaatIni(int $masterBarangId): int
    {
        return $this->totalMasuk($masterBarangId)
            + $this->totalKoreksi($masterBarangId)
            - $this->totalKeluar($masterBarangId);
    }

    /**
     * Sisa stok TEPAT SEBELUM transaksi tertentu diproses.
     * Ini yang dipakai di kolom "Informasi Sisa Barang Persediaan" pas generate surat SPB (Fase 7).
     */
    public function sisaSebelumTransaksi(int $masterBarangId, Transaksi $transaksi): int
    {
        return $this->totalMasuk($masterBarangId)
            + $this->totalKoreksi($masterBarangId)
            - $this->totalKeluar($masterBarangId, $transaksi);
    }
}
