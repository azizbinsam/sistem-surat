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
            'promo' => config('langganan.promo'),
            'videoTutorial' => Schema::hasTable('video_tutorial')
                ? VideoTutorial::orderBy('urutan')->get()
                : collect(),
        ]);
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
