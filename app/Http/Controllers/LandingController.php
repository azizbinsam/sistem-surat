<?php

namespace App\Http\Controllers;

use App\Models\PaketLangganan;
use App\Models\VideoTutorial;
use Illuminate\Support\Facades\Schema;

class LandingController extends Controller
{
    public function __invoke()
    {
        return view('welcome', [
            'harga' => $this->harga(),
            'gratis' => $this->gratis(),
            'videoTutorial' => Schema::hasTable('video_tutorial')
                ? VideoTutorial::orderBy('urutan')->get()
                : collect(),
        ]);
    }

    /**
     * Teks jendela gratis untuk landing page, disusun dari config('langganan.gratis') supaya mengubah
     * batas gratis cukup di satu tempat (config/.env), tanpa menyunting teks landing.
     *
     * @return array{tampil:bool, rentang:string, mulai_berbayar:string}
     */
    protected function gratis(): array
    {
        $tahun = (int) config('langganan.gratis.tahun');
        $sampai = (int) config('langganan.gratis.sampai_triwulan');

        $tampil = $tahun > 0 && $sampai >= 1 && (bool) config('langganan.gratis.tampil_di_landing', true);

        // triwulan pertama yang berbayar; kalau jendela gratis menutup TW4, berbayar mulai TW1 tahun berikutnya
        $mulaiTriwulan = $sampai >= 4 ? 1 : $sampai + 1;
        $mulaiTahun = $sampai >= 4 ? $tahun + 1 : $tahun;

        return [
            'tampil' => $tampil,
            'rentang' => ($sampai <= 1 ? 'Triwulan 1' : "Triwulan 1–{$sampai}") . " Tahun Anggaran {$tahun}",
            'mulai_berbayar' => "Triwulan {$mulaiTriwulan} Tahun Anggaran {$mulaiTahun}",
        ];
    }

    /**
     * Harga dibaca dari katalog (menu admin "Paket & Harga"), jadi mengubah harga di admin langsung
     * tampil di landing page tanpa deploy. Kalau katalog kosong/belum ada, bagian harga disembunyikan.
     *
     * @return array{triwulan:int, tahunan:int, hemat:int, per_triwulan_tahunan:int}|null
     */
    protected function harga(): ?array
    {
        if (!Schema::hasTable('paket_langganan')) {
            return null;
        }

        $paket = PaketLangganan::whereIn('kode', ['triwulan', 'tahunan'])->get()->keyBy('kode');

        if (!$paket->has('triwulan') || !$paket->has('tahunan')) {
            return null;
        }

        $triwulan = (int) $paket['triwulan']->harga;
        $tahunan = (int) $paket['tahunan']->harga;

        return [
            'triwulan' => $triwulan,
            'tahunan' => $tahunan,
            // selisih dibanding membeli 4 triwulan satuan; 0 (atau minus) berarti tidak ada diskon untuk ditonjolkan
            'hemat' => max(0, 4 * $triwulan - $tahunan),
            'per_triwulan_tahunan' => intdiv($tahunan, 4),
        ];
    }
}
