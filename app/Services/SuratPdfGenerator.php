<?php

namespace App\Services;

use App\Models\Pegawai;
use App\Models\Transaksi;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class SuratPdfGenerator
{
    public function __construct(protected PersediaanService $persediaan) {}

    public function generate(Transaksi $transaksi): string
    {
        $sekolah = $transaksi->sekolah;
        $peminta = $transaksi->pihakPeminta;
        $pbp = Pegawai::where('sekolah_id', $sekolah->id)->where('kategori', 'pengurus_barang_pembantu')->first();
        $kepsek = Pegawai::where('sekolah_id', $sekolah->id)->where('kategori', 'kepala_sekolah')->first();
        $items = $transaksi->items()->with('masterBarang')->get();

        $sisaPerItem = [];
        foreach ($items as $item) {
            $sisaPerItem[$item->id] = $this->persediaan->sisaSebelumTransaksi($item->master_barang_id, $transaksi);
        }

        $tanggalFormat = [
            'npb' => Carbon::parse($transaksi->tanggal_npb)->locale('id')->isoFormat('D MMMM YYYY'),
            'spb' => Carbon::parse($transaksi->tanggal_spb)->locale('id')->isoFormat('D MMMM YYYY'),
            'sppb' => Carbon::parse($transaksi->tanggal_sppb)->locale('id')->isoFormat('D MMMM YYYY'),
        ];

        $pdf = Pdf::loadView('pdf.surat', compact(
            'sekolah',
            'transaksi',
            'peminta',
            'pbp',
            'kepsek',
            'items',
            'sisaPerItem',
            'tanggalFormat'
        ))->setPaper('a4');

        $outputDir = storage_path('app/generated');
        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }
        $filename = 'Surat_' . str_replace(['/', ' '], '_', $transaksi->nomor_npb) . '.pdf';
        $outputPath = $outputDir . '/' . $filename;
        $pdf->save($outputPath);

        return $outputPath;
    }
}
