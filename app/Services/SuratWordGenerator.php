<?php

namespace App\Services;

use App\Models\Pegawai;
use App\Models\Transaksi;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\TemplateProcessor;

class SuratWordGenerator
{
    public function __construct(protected PersediaanService $persediaan) {}

    public function generate(Transaksi $transaksi): string
    {
        $sekolah = $transaksi->sekolah;
        $processor = new TemplateProcessor(storage_path('app/templates/template_surat.docx'));

        // ==== KOP (muncul 3x di dokumen - NPB, SPB, SPPB - limit diisi eksplisit 3) ====
        $processor->setValue('nama_pemerintah', e($sekolah->nama_pemerintah), 3);
        $processor->setValue('nama_dinas', e($sekolah->nama_dinas), 3);
        $processor->setValue('nama_korwil', e($sekolah->nama_korwil), 3);
        $processor->setValue('nama_sekolah', e($sekolah->nama_sekolah), 3);
        $processor->setValue('alamat_sekolah', e($sekolah->alamat), 3);
        $this->setLogoOrClear($processor, 'logo_kabupaten', $sekolah->logo_kabupaten, 3);
        $this->setLogoOrClear($processor, 'logo_sekolah', $sekolah->logo_sekolah, 3);

        // ==== NPB ====
        $peminta = $transaksi->pihakPeminta;
        $processor->setValue('nomor_npb', $transaksi->nomor_npb);
        // jabatan_peminta muncul 2x (baris "pihak yang meminta" + blok TTD)
        $processor->setValue('jabatan_peminta', e($peminta->jabatan), 4);
        // tempat muncul 3x (1 per surat)
        $processor->setValue('tempat', e($sekolah->tempat), 3);
        $processor->setValue('tanggal_npb', $this->formatTanggal($transaksi->tanggal_npb));
        $processor->setValue('nama_peminta', e($peminta->nama));
        $processor->setValue('nip_peminta', $peminta->nip ?? '-');
        $this->setTtdOrClear($processor, 'ttd_peminta', $peminta->ttd_path);

        // ==== SPB ====
        $pbp = Pegawai::where('sekolah_id', $sekolah->id)
            ->where('kategori', 'pengurus_barang_pembantu')->first();

        $processor->setValue('nomor_spb', $transaksi->nomor_spb);
        $processor->setValue('tanggal_spb', $this->formatTanggal($transaksi->tanggal_spb));
        $processor->setValue('jabatan_pbp', e($pbp->jabatan ?? '-'));
        $processor->setValue('nama_pbp', e($pbp->nama ?? '-'));
        $processor->setValue('nip_pbp', $pbp->nip ?? '-');
        $this->setTtdOrClear($processor, 'ttd_pbp', $pbp->ttd_path ?? null);

        // ==== SPPB ====
        $kepsek = Pegawai::where('sekolah_id', $sekolah->id)
            ->where('kategori', 'kepala_sekolah')->first();

        $processor->setValue('nomor_sppb', $transaksi->nomor_sppb);
        $processor->setValue('tanggal_sppb', $this->formatTanggal($transaksi->tanggal_sppb));
        $processor->setValue('jabatan_resmi_sppb', e($sekolah->jabatan_resmi_sppb));
        $processor->setValue('nama_kepsek', e($kepsek->nama ?? '-'));
        $processor->setValue('nip_kepsek', $kepsek->nip ?? '-');
        $this->setTtdOrClear($processor, 'ttd_kepsek', $kepsek->ttd_path ?? null);

        // ==== TABEL ITEM ====
        $items = $transaksi->items()->with('masterBarang')->get();
        $this->isiTabelNpb($processor, $items);
        $this->isiTabelSpb($processor, $items, $transaksi);
        $this->isiTabelSppb($processor, $items);

        // ==== simpan file ====
        $outputDir = storage_path('app/generated');
        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }
        $filename = 'Surat_' . str_replace(['/', ' '], '_', $transaksi->nomor_npb) . '.docx';
        $outputPath = $outputDir . '/' . $filename;
        $processor->saveAs($outputPath);

        return $outputPath;
    }

    protected function isiTabelNpb(TemplateProcessor $processor, $items): void
    {
        $processor->cloneRow('no_npb', $items->count());
        foreach ($items as $i => $item) {
            $n = $i + 1;
            $processor->setValue("no_npb#$n", $n);
            $processor->setValue("spesifikasi_npb#$n", e($item->spesifikasi));
            $processor->setValue("jumlah_npb#$n", $item->jumlah);
            $processor->setValue("satuan_npb#$n", e($item->satuan));
            $processor->setValue("keperluan_npb#$n", e($item->keperluan));
        }
    }

    protected function isiTabelSpb(TemplateProcessor $processor, $items, Transaksi $transaksi): void
    {
        $processor->cloneRow('no_spb', $items->count());
        foreach ($items as $i => $item) {
            $n = $i + 1;
            $sisa = $this->persediaan->sisaSebelumTransaksi($item->master_barang_id, $transaksi);

            $processor->setValue("no_spb#$n", $n);
            $processor->setValue("kode_barang_spb#$n", e($item->masterBarang->kode_barang));
            $processor->setValue("nama_barang_spb#$n", e($item->masterBarang->nama_barang));
            $processor->setValue("spesifikasi_spb#$n", e($item->spesifikasi));
            $processor->setValue("jml_pengajuan#$n", $item->jumlah);
            $processor->setValue("satuan_pengajuan#$n", e($item->satuan));
            $processor->setValue("jml_sisa#$n", $sisa);
            $processor->setValue("satuan_sisa#$n", e($item->masterBarang->satuan_default));
            $processor->setValue("jml_usulan#$n", $item->jumlah);
            $processor->setValue("satuan_usulan#$n", e($item->satuan));
            $processor->setValue("keperluan_spb#$n", e($item->keperluan));
        }
    }

    protected function isiTabelSppb(TemplateProcessor $processor, $items): void
    {
        $processor->cloneRow('no_sppb', $items->count());
        foreach ($items as $i => $item) {
            $n = $i + 1;
            $processor->setValue("no_sppb#$n", $n);
            $processor->setValue("kode_barang_sppb#$n", e($item->masterBarang->kode_barang));
            $processor->setValue("nama_barang_sppb#$n", e($item->masterBarang->nama_barang));
            $processor->setValue("spesifikasi_sppb#$n", e($item->spesifikasi));
            $processor->setValue("jml_setuju#$n", $item->jumlah);
            $processor->setValue("satuan_setuju#$n", e($item->satuan));
        }
    }

    protected function formatTanggal($tanggal): string
    {
        return Carbon::parse($tanggal)->locale('id')->isoFormat('D MMMM YYYY');
    }

    protected function setLogoOrClear(TemplateProcessor $processor, string $key, ?string $path, int $limit = 1): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            $processor->setImageValue($key, [
                'path' => Storage::disk('public')->path($path),
                'width' => 70,
                'height' => 70,
                'ratio' => true,
            ]);
        } else {
            $processor->setValue($key, '', $limit);
        }
    }

    protected function setTtdOrClear(TemplateProcessor $processor, string $key, ?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            $processor->setImageValue($key, [
                'path' => Storage::disk('public')->path($path),
                'width' => 120,
                'height' => 60,
                'ratio' => true,
            ]);
        } else {
            $processor->setValue($key, '');
        }
    }
}
