<?php

namespace App\Services;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Kwitansi pembayaran langganan (PDF). Satu-satunya pemakai dompdf sejak surat NPB/SPB/SPPB hanya Word.
 */
class KwitansiPdfGenerator
{
    public function nomorKwitansi(Invoice $invoice): string
    {
        return str_replace('INV-', 'KWT-', (string) $invoice->nomor);
    }

    public function namaFile(Invoice $invoice): string
    {
        return 'Kwitansi-' . $this->nomorKwitansi($invoice) . '.pdf';
    }

    /** Isi PDF sebagai string biner (bisa dikirim sebagai respons atau dilampirkan ke email). */
    public function render(Invoice $invoice): string
    {
        $invoice->loadMissing('sekolah', 'items.tahunAnggaran');

        return Pdf::loadView('pdf.kwitansi', [
            'invoice' => $invoice,
            'sekolah' => $invoice->sekolah,
            'nomorKwitansi' => $this->nomorKwitansi($invoice),
            'penerbit' => config('langganan.penerbit'),
        ])->setPaper('a5', 'landscape')->output();
    }
}
