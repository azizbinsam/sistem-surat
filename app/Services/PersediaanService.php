<?php

namespace App\Services;

use App\Models\BarangMasukItem;
use App\Models\KoreksiStok;
use App\Models\Transaksi;
use App\Models\TransaksiItem;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class PersediaanService
{
    /**
     * Terapkan cutoff tanggal+created_at konsisten ke query manapun.
     * - Kalau $cutoffCreatedAt diisi: baris dianggap "sudah ada" kalau tanggal < cutoffTanggal,
     *   ATAU (tanggal == cutoffTanggal DAN created_at < cutoffCreatedAt). Dipakai buat
     *   "sisa sebelum transaksi X" — exclude transaksi itu sendiri & yang after dia.
     * - Kalau $cutoffCreatedAt null: baris dianggap "sudah ada" kalau tanggal <= cutoffTanggal
     *   (inklusif). Dipakai buat "sisa saat ini" (cutoff = hari ini).
     *
     * $idColumn + $cutoffId (opsional): tie-breaker PENGGANTI created_at, khusus buat
     * perbandingan di tabel yang SAMA (misal Transaksi vs Transaksi lain). created_at
     * MySQL presisinya cuma sampai DETIK — kalau banyak baris dibuat dalam request yang
     * sama (upload Excel bulk), created_at-nya bisa identik dan tie-breaker itu gagal
     * bedain mana yang duluan. id auto-increment selalu urut sesuai urutan insert,
     * nggak peduli presisi timestamp, jadi lebih akurat buat kasus ini. TIDAK bisa
     * dipakai buat perbandingan LINTAS tabel (misal BarangMasuk vs Transaksi) karena
     * id di tabel beda itu 2 sequence independen yang nggak ada hubungan urutannya.
     */
    protected function applyCutoff(Builder $query, string $dateColumn, Carbon $cutoffTanggal, ?Carbon $cutoffCreatedAt = null, ?string $idColumn = null, ?int $cutoffId = null): Builder
    {
        return $query->where(function ($q) use ($dateColumn, $cutoffTanggal, $cutoffCreatedAt, $idColumn, $cutoffId) {
            $q->where($dateColumn, '<', $cutoffTanggal->toDateString());

            if ($idColumn && $cutoffId !== null) {
                $q->orWhere(function ($qq) use ($dateColumn, $cutoffTanggal, $idColumn, $cutoffId) {
                    $qq->where($dateColumn, $cutoffTanggal->toDateString())
                        ->where($idColumn, '<', $cutoffId);
                });
            } elseif ($cutoffCreatedAt) {
                $q->orWhere(function ($qq) use ($dateColumn, $cutoffTanggal, $cutoffCreatedAt) {
                    $qq->where($dateColumn, $cutoffTanggal->toDateString())
                        ->where('created_at', '<', $cutoffCreatedAt);
                });
            } else {
                $q->orWhere($dateColumn, '<=', $cutoffTanggal->toDateString());
            }
        });
    }

    public function totalMasuk(int $masterBarangId, ?Carbon $cutoffTanggal = null, ?Carbon $cutoffCreatedAt = null): int
    {
        $query = BarangMasukItem::where('master_barang_id', $masterBarangId);

        if ($cutoffTanggal) {
            $query->whereHas('barangMasuk', function ($q) use ($cutoffTanggal, $cutoffCreatedAt) {
                $this->applyCutoff($q, 'tanggal', $cutoffTanggal, $cutoffCreatedAt);
            });
        }

        return (int) $query->sum('jumlah');
    }

    public function totalKoreksi(int $masterBarangId, ?Carbon $cutoffTanggal = null, ?Carbon $cutoffCreatedAt = null): int
    {
        $query = KoreksiStok::where('master_barang_id', $masterBarangId);

        if ($cutoffTanggal) {
            $this->applyCutoff($query, 'tanggal', $cutoffTanggal, $cutoffCreatedAt);
        }

        return (int) $query->sum('jumlah');
    }

    public function totalKeluar(int $masterBarangId, ?Carbon $cutoffTanggal = null, ?Carbon $cutoffCreatedAt = null, ?int $excludeTransaksiId = null, ?int $cutoffTransaksiId = null): int
    {
        $query = TransaksiItem::where('master_barang_id', $masterBarangId);

        if ($cutoffTanggal) {
            $query->whereHas('transaksi', function ($q) use ($cutoffTanggal, $cutoffCreatedAt, $excludeTransaksiId, $cutoffTransaksiId) {
                // Transaksi vs Transaksi -> boleh pakai id sebagai tie-breaker
                // (lihat penjelasan panjang di applyCutoff()).
                $this->applyCutoff($q, 'tanggal_npb', $cutoffTanggal, $cutoffCreatedAt, idColumn: 'id', cutoffId: $cutoffTransaksiId);

                if ($excludeTransaksiId) {
                    $q->where('id', '!=', $excludeTransaksiId);
                }
            });
        }

        return (int) $query->sum('jumlah');
    }

    /**
     * Sisa stok SAAT INI (cutoff = hari ini, inklusif). Dipakai di halaman ringkasan persediaan.
     */
    public function sisaSaatIni(int $masterBarangId): int
    {
        $now = now();

        return $this->totalMasuk($masterBarangId, $now)
            + $this->totalKoreksi($masterBarangId, $now)
            - $this->totalKeluar($masterBarangId, $now);
    }

    /**
     * Sisa stok TEPAT SEBELUM transaksi tertentu diproses (exclude transaksi itu sendiri).
     * Dipakai di kolom "Informasi Sisa Barang Persediaan" pas generate surat SPB,
     * dan preview draft/detail transaksi.
     */
    public function sisaSebelumTransaksi(int $masterBarangId, Transaksi $transaksi): int
    {
        $cutoffTanggal = Carbon::parse($transaksi->tanggal_npb);
        $cutoffCreatedAt = $transaksi->created_at;

        return $this->totalMasuk($masterBarangId, $cutoffTanggal, $cutoffCreatedAt)
            + $this->totalKoreksi($masterBarangId, $cutoffTanggal, $cutoffCreatedAt)
            - $this->totalKeluar($masterBarangId, $cutoffTanggal, $cutoffCreatedAt, excludeTransaksiId: $transaksi->id, cutoffTransaksiId: $transaksi->id);
    }
}
