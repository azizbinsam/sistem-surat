<?php

namespace App\Services;

use App\Models\HakTriwulan;
use App\Models\Invoice;
use App\Models\Sekolah;
use App\Models\TahunAnggaran;
use App\Models\Transaksi;
use App\Models\User;
use App\Support\Triwulan;
use Carbon\Carbon;

/**
 * Tiga pengingat terjadwal (doc/LANGGANAN.md §9). Dijalankan sekali sehari oleh
 * `langganan:kirim-pengingat`, jadi tiap pengingat sengaja dibuat "cukup sekali per kejadian"
 * lewat kondisi tanggalnya — tidak perlu kolom penanda "sudah diingatkan".
 */
class PengingatLanggananService
{
    public function __construct(protected LanggananNotifier $notifier, protected AksesTriwulanService $akses)
    {
    }

    /** Sekolah: invoice belum dibayar yang jatuh temponya TEPAT 2 hari lagi (hari kalender). */
    public function invoiceHampirKedaluwarsa(Carbon $hariIni): int
    {
        $jumlah = 0;

        $daftar = Invoice::where('status', Invoice::MENUNGGU_PEMBAYARAN)
            ->whereDate('jatuh_tempo_at', $hariIni->copy()->addDays(2)->toDateString())
            ->with('sekolah.users', 'items.tahunAnggaran')
            ->get();

        foreach ($daftar as $invoice) {
            $jumlah += $this->notifier->invoiceHampirKedaluwarsa($invoice);
        }

        return $jumlah;
    }

    /**
     * Admin: invoice yang bukti transfernya sudah > 24 jam tapi belum diverifikasi. Satu email berisi
     * daftar lengkapnya ke semua admin; terkirim tiap hari selama masih ada yang menggantung.
     */
    public function invoiceMenungguVerifikasi(Carbon $sekarang): int
    {
        $daftar = Invoice::where('status', Invoice::MENUNGGU_VERIFIKASI)
            ->where('dibayar_at', '<', $sekarang->copy()->subDay())
            ->with('sekolah')
            ->orderBy('dibayar_at')
            ->get();

        if ($daftar->isEmpty()) {
            return 0;
        }

        $emailAdmin = User::where('role', 'admin')->whereNotNull('email')->pluck('email')->all();

        if (empty($emailAdmin)) {
            return 0;
        }

        $baris = $daftar->map(fn($i) => [
            'nomor' => $i->nomor,
            'sekolah' => $i->sekolah->nama_sekolah ?? '-',
            'total' => number_format($i->total_bayar, 0, ',', '.'),
            'umur' => $this->umur((int) floor(abs($i->dibayar_at->diffInHours($sekarang)))),
        ])->all();

        return $this->notifier->adminMenungguVerifikasi($baris, $emailAdmin);
    }

    protected function umur(int $jam): string
    {
        return $jam >= 48 ? intdiv($jam, 24) . ' hari' : $jam . ' jam';
    }

    /**
     * Sekolah: pada tanggal 1 bulan pertama tiap triwulan (1 Jan/Apr/Jul/Okt), beri tahu sekolah yang masih
     * punya transaksi DRAFT di triwulan yang baru berakhir padahal triwulan itu belum punya hak.
     * Hanya jalan bila pengunci langganan aktif.
     */
    public function draftBelumBisaGenerate(Carbon $hariIni): int
    {
        if (!config('langganan.pengunci_aktif')) {
            return 0;
        }

        if ($hariIni->day !== 1 || !in_array($hariIni->month, [1, 4, 7, 10], true)) {
            return 0;
        }

        $hariTerakhirTriwulanLalu = $hariIni->copy()->subDay();
        $triwulanLalu = $hariTerakhirTriwulanLalu->quarter;
        $tahunLalu = $hariTerakhirTriwulanLalu->year;
        [$awal, $akhir] = Triwulan::rentang($tahunLalu, $triwulanLalu);

        // triwulan yang gratis tidak perlu diingatkan untuk dibeli
        if ($this->akses->gratisBerdasarkanAturan($tahunLalu, $triwulanLalu)) {
            return 0;
        }

        $jumlah = 0;

        foreach (Sekolah::with('users')->get() as $sekolah) {
            $tahunAnggaran = TahunAnggaran::where('sekolah_id', $sekolah->id)->where('tahun', $tahunLalu)->first();
            if (!$tahunAnggaran) {
                continue;
            }

            $sudahPunyaHak = HakTriwulan::where('sekolah_id', $sekolah->id)
                ->where('tahun_anggaran_id', $tahunAnggaran->id)
                ->where('triwulan', $triwulanLalu)
                ->aktif()
                ->exists();
            if ($sudahPunyaHak) {
                continue;
            }

            // withoutGlobalScopes: di konteks terjadwal tidak ada user login, jadi kita filter tahun anggaran manual
            $jumlahDraft = Transaksi::withoutGlobalScopes()
                ->where('sekolah_id', $sekolah->id)
                ->where('tahun_anggaran_id', $tahunAnggaran->id)
                ->where('status', 'draft')
                ->whereBetween('tanggal_npb', [$awal->toDateString(), $akhir->toDateString()])
                ->count();

            if ($jumlahDraft === 0) {
                continue;
            }

            $jumlah += $this->notifier->draftBelumDigenerate($sekolah, $jumlahDraft, "TW{$triwulanLalu} {$tahunLalu}");
        }

        return $jumlah;
    }
}
