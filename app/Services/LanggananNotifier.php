<?php

namespace App\Services;

use App\Mail\NotifikasiLanggananMail;
use App\Models\Invoice;
use App\Models\Sekolah;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Mengirim email langganan. Prinsip utamanya: GAGAL KIRIM EMAIL TIDAK BOLEH menggagalkan aksi utama
 * (mis. persetujuan pembayaran). Semua pengiriman dibungkus try/catch; kegagalan hanya dicatat di log
 * (report()), sedangkan metode ini mengembalikan jumlah penerima yang berhasil dikirimi.
 */
class LanggananNotifier
{
    public function __construct(protected KwitansiPdfGenerator $kwitansi)
    {
    }

    public function invoiceDisetujui(Invoice $invoice): int
    {
        $invoice->loadMissing('sekolah.users', 'items.tahunAnggaran');

        $pdf = null;
        try {
            $pdf = $this->kwitansi->render($invoice);
        } catch (Throwable $e) {
            // kwitansi gagal dibuat -> email tetap dikirim tanpa lampiran (sekolah masih bisa unduh dari halaman invoice)
            report($e);
        }

        return $this->kirimKeSekolah($invoice->sekolah, new NotifikasiLanggananMail(
            'Pembayaran langganan kamu sudah diverifikasi',
            'disetujui',
            [
                'nomorInvoice' => $invoice->nomor,
                'totalBayar' => number_format($invoice->total_bayar, 0, ',', '.'),
                'daftarTriwulan' => $this->daftarTriwulan($invoice),
                'invoiceUrl' => route('langganan.invoice', $invoice),
            ],
            $pdf,
            $this->kwitansi->namaFile($invoice),
        ));
    }

    public function invoiceDitolak(Invoice $invoice): int
    {
        $invoice->loadMissing('sekolah.users');

        return $this->kirimKeSekolah($invoice->sekolah, new NotifikasiLanggananMail(
            'Bukti transfer invoice ' . $invoice->nomor . ' belum bisa diterima',
            'ditolak',
            [
                'nomorInvoice' => $invoice->nomor,
                'alasan' => (string) $invoice->catatan_admin,
                'batasUpload' => $invoice->jatuh_tempo_at->format('d-m-Y'),
                'invoiceUrl' => route('langganan.invoice', $invoice),
            ],
        ));
    }

    public function invoiceHampirKedaluwarsa(Invoice $invoice): int
    {
        $invoice->loadMissing('sekolah.users', 'items.tahunAnggaran');

        return $this->kirimKeSekolah($invoice->sekolah, new NotifikasiLanggananMail(
            'Invoice ' . $invoice->nomor . ' hampir kedaluwarsa',
            'hampir-kedaluwarsa',
            [
                'nomorInvoice' => $invoice->nomor,
                'daftarTriwulan' => $this->daftarTriwulan($invoice),
                'jatuhTempo' => $invoice->jatuh_tempo_at->format('d-m-Y H:i'),
                'totalBayar' => number_format($invoice->total_bayar, 0, ',', '.'),
                'invoiceUrl' => route('langganan.invoice', $invoice),
            ],
        ));
    }

    public function draftBelumDigenerate(Sekolah $sekolah, int $jumlahDraft, string $namaTriwulan): int
    {
        return $this->kirimKeSekolah($sekolah, new NotifikasiLanggananMail(
            "Ada {$jumlahDraft} transaksi menunggu di-generate",
            'draft-belum-digenerate',
            [
                'jumlahDraft' => $jumlahDraft,
                'namaTriwulan' => $namaTriwulan,
                'langgananUrl' => route('langganan.index'),
            ],
        ));
    }

    /**
     * @param  list<array{nomor:string, sekolah:string, total:string, umur:string}>  $daftar
     */
    public function adminMenungguVerifikasi(array $daftar, array $emailAdmin): int
    {
        $terkirim = 0;

        foreach ($emailAdmin as $email) {
            try {
                Mail::to($email)->send(new NotifikasiLanggananMail(
                    count($daftar) . ' invoice menunggu verifikasi',
                    'admin-menunggu-verifikasi',
                    ['daftar' => $daftar, 'adminUrl' => url('/admin/invoices')],
                ));
                $terkirim++;
            } catch (Throwable $e) {
                report($e);
            }
        }

        return $terkirim;
    }

    protected function kirimKeSekolah(?Sekolah $sekolah, NotifikasiLanggananMail $mail): int
    {
        if (!$sekolah) {
            return 0;
        }

        $terkirim = 0;

        foreach ($sekolah->users()->whereNotNull('email')->pluck('email')->unique() as $email) {
            try {
                // objek baru per penerima supaya satu pengiriman gagal tidak memengaruhi yang lain
                Mail::to($email)->send(clone $mail);
                $terkirim++;
            } catch (Throwable $e) {
                report($e);
            }
        }

        return $terkirim;
    }

    protected function daftarTriwulan(Invoice $invoice): string
    {
        return $invoice->items
            ->sortBy('triwulan')
            ->map(fn($i) => "TW{$i->triwulan} {$i->tahunAnggaran->tahun}")
            ->implode(', ');
    }
}
