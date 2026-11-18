<?php

namespace App\Services;

use App\Exceptions\LanggananException;
use App\Models\HakTriwulan;
use App\Models\PaketLangganan;
use App\Models\Sekolah;
use App\Models\TahunAnggaran;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * "Apakah sekolah ini boleh generate surat untuk triwulan X?" + hitung harga paket.
 * Aturan lengkapnya ada di doc/LANGGANAN.md §3–§4.
 */
class AksesTriwulanService
{
    public function pengunciAktif(): bool
    {
        return (bool) config('langganan.pengunci_aktif');
    }

    // ===== Harga =====

    public function hargaTriwulan(): int
    {
        return PaketLangganan::harga('triwulan');
    }

    public function hargaTahunan(): int
    {
        return PaketLangganan::harga('tahunan');
    }

    /**
     * harga = min( jumlah_triwulan × harga_triwulan , harga_tahunan ).
     * Contoh (39.000 & 129.000): 1 TW = 39.000, 3 TW = 117.000, 4 TW = 129.000 (diskon 27.000).
     *
     * @return array{subtotal:int, diskon:int, total:int}
     */
    public function hitungHarga(int $jumlahTriwulan): array
    {
        $subtotal = max(0, $jumlahTriwulan) * $this->hargaTriwulan();
        $total = $jumlahTriwulan > 0 ? min($subtotal, $this->hargaTahunan()) : 0;

        return ['subtotal' => $subtotal, 'diskon' => $subtotal - $total, 'total' => $total];
    }

    // ===== Hak & kuota =====

    /**
     * Semua hak aktif milik sekolah, dengan kunci "{tahun_anggaran_id}-{triwulan}".
     *
     * @return array<string, HakTriwulan>
     */
    public function hakAktifMap(Sekolah $sekolah): array
    {
        return HakTriwulan::where('sekolah_id', $sekolah->id)
            ->aktif()
            ->get()
            ->keyBy(fn($h) => $h->tahun_anggaran_id . '-' . $h->triwulan)
            ->all();
    }

    // ===== Pemberian & pencabutan hak oleh admin =====

    /**
     * Admin memberi hak triwulan tanpa pembayaran (promo, komplimen, sekolah percontohan).
     * sumber: 'gratis' (biasanya dengan batas waktu) atau 'manual' (diberikan admin, biasanya tanpa batas).
     * Kalau hak untuk triwulan itu sudah ada (mis. sebelumnya dicabut atau gratis kedaluwarsa), barisnya diperbarui
     * — satu (tahun anggaran, triwulan) hanya boleh punya satu baris. Hak BERBAYAR yang masih aktif tidak ditimpa.
     */
    public function berikanHak(Sekolah $sekolah, TahunAnggaran $tahunAnggaran, int $triwulan, string $sumber, ?CarbonInterface $berlakuSampai = null, ?string $catatan = null): HakTriwulan
    {
        if ($tahunAnggaran->sekolah_id !== $sekolah->id) {
            throw new LanggananException('Tahun anggaran bukan milik sekolah ini.');
        }
        if ($triwulan < 1 || $triwulan > 4) {
            throw new LanggananException('Triwulan harus antara 1 sampai 4.');
        }
        if (!in_array($sumber, ['gratis', 'manual'], true)) {
            throw new LanggananException('Sumber hak harus gratis atau manual.');
        }

        $hak = HakTriwulan::where('tahun_anggaran_id', $tahunAnggaran->id)->where('triwulan', $triwulan)->first();

        if ($hak && $hak->sumber === 'berbayar' && $hak->status === 'aktif') {
            throw new LanggananException("TW{$triwulan} {$tahunAnggaran->tahun} sudah dibeli sekolah ini (hak berbayar aktif).");
        }

        $data = [
            'sumber' => $sumber,
            'status' => 'aktif',
            'berlaku_sampai' => $berlakuSampai?->toDateString(),
            'catatan' => $catatan,
        ];

        if ($hak) {
            $hak->update($data);

            return $hak;
        }

        return HakTriwulan::create($data + [
            'sekolah_id' => $sekolah->id,
            'tahun_anggaran_id' => $tahunAnggaran->id,
            'triwulan' => $triwulan,
        ]);
    }

    /** Cabut hak (status → dicabut). Surat yang sudah di-generate tidak terpengaruh; hanya generate berikutnya. */
    public function cabutHak(HakTriwulan $hak): HakTriwulan
    {
        $hak->update(['status' => 'dicabut']);

        return $hak;
    }

    public function aktifkanKembaliHak(HakTriwulan $hak): HakTriwulan
    {
        $hak->update(['status' => 'aktif']);

        return $hak;
    }

    public function kuotaPercobaanSisa(Sekolah $sekolah): int
    {
        return max(0, (int) config('langganan.kuota_percobaan') - (int) $sekolah->generate_percobaan_terpakai);
    }

    /**
     * Periksa apakah kumpulan transaksi (yang BELUM di-generate) boleh di-generate sekarang.
     * Aturan: transaksi di triwulan yang sudah punya hak aktif → lolos. Sisanya memakai kuota percobaan,
     * dan hanya lolos kalau kuota cukup untuk SEMUANYA (all-or-nothing, sama seperti aturan bulk lainnya).
     * Pemanggil bertanggung jawab menaikkan `generate_percobaan_terpakai` sebesar `pakai_kuota` setelah berhasil.
     *
     * @param  Collection  $transaksi  model Transaksi dengan relasi tahunAnggaran sudah dimuat
     * @return array{boleh:bool, pakai_kuota:int, sisa_kuota:int, pesan:?string}
     */
    public function periksaGenerate(Sekolah $sekolah, Collection $transaksi): array
    {
        $sisaKuota = $this->kuotaPercobaanSisa($sekolah);

        if (!$this->pengunciAktif()) {
            return ['boleh' => true, 'pakai_kuota' => 0, 'sisa_kuota' => $sisaKuota, 'pesan' => null];
        }

        $hakMap = $this->hakAktifMap($sekolah);

        // kelompokkan transaksi yang TIDAK punya hak per (tahun anggaran, triwulan)
        $kurang = [];
        foreach ($transaksi as $t) {
            $triwulan = $t->tanggal_npb->quarter;
            $kunci = $t->tahun_anggaran_id . '-' . $triwulan;

            if (isset($hakMap[$kunci])) {
                continue;
            }

            $kurang[$kunci] ??= ['tahun' => $t->tahunAnggaran->tahun ?? '?', 'triwulan' => $triwulan, 'jumlah' => 0];
            $kurang[$kunci]['jumlah']++;
        }

        $butuh = array_sum(array_column($kurang, 'jumlah'));

        if ($butuh === 0) {
            return ['boleh' => true, 'pakai_kuota' => 0, 'sisa_kuota' => $sisaKuota, 'pesan' => null];
        }

        if ($butuh <= $sisaKuota) {
            return ['boleh' => true, 'pakai_kuota' => $butuh, 'sisa_kuota' => $sisaKuota, 'pesan' => null];
        }

        ksort($kurang);
        $daftar = collect($kurang)
            ->map(fn($k) => "TW{$k['triwulan']} {$k['tahun']} ({$k['jumlah']} transaksi)")
            ->implode(', ');

        $pesan = "Belum bisa generate. Triwulan berikut belum dibeli: {$daftar}. "
            . ($sisaKuota > 0
                ? "Kuota percobaan gratis tinggal {$sisaKuota}, sedangkan dibutuhkan {$butuh}."
                : 'Kuota percobaan gratis sudah habis.');

        return ['boleh' => false, 'pakai_kuota' => 0, 'sisa_kuota' => $sisaKuota, 'pesan' => $pesan];
    }
}
