<?php

namespace App\Services;

use App\Exceptions\LanggananException;
use App\Models\HakTriwulan;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Sekolah;
use App\Models\TahunAnggaran;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Siklus hidup invoice: buat → (sekolah) upload bukti → (admin) setujui/tolak → hak triwulan aktif.
 * Alur manual & iPaymu nanti berbeda HANYA di langkah konfirmasi; setujui() dipakai keduanya.
 */
class InvoiceService
{
    public function __construct(protected AksesTriwulanService $akses, protected LanggananNotifier $notifier)
    {
    }

    /**
     * @param  int[]  $triwulan  nomor triwulan yang mau dibeli (1–4)
     */
    public function buatInvoice(Sekolah $sekolah, TahunAnggaran $tahunAnggaran, array $triwulan): Invoice
    {
        if ($tahunAnggaran->sekolah_id !== $sekolah->id) {
            throw new LanggananException('Tahun anggaran tidak valid.');
        }

        $triwulan = collect($triwulan)->map(fn($t) => (int) $t)->unique()->sort()->values();

        if ($triwulan->isEmpty()) {
            throw new LanggananException('Pilih minimal 1 triwulan.');
        }
        if ($triwulan->contains(fn($t) => $t < 1 || $t > 4)) {
            throw new LanggananException('Triwulan harus antara 1 sampai 4.');
        }

        return DB::transaction(function () use ($sekolah, $tahunAnggaran, $triwulan) {
            $hakMap = $this->akses->hakAktifMap($sekolah);

            $sudahPunya = $triwulan->filter(fn($t) => isset($hakMap[$tahunAnggaran->id . '-' . $t]));
            if ($sudahPunya->isNotEmpty()) {
                throw new LanggananException('Triwulan ' . $sudahPunya->map(fn($t) => "TW{$t}")->implode(', ') . ' sudah dibeli atau masih gratis.');
            }

            $sedangDipesan = InvoiceItem::query()
                ->whereHas('invoice', fn($q) => $q->where('sekolah_id', $sekolah->id)->whereIn('status', Invoice::PENDING))
                ->where('tahun_anggaran_id', $tahunAnggaran->id)
                ->whereIn('triwulan', $triwulan->all())
                ->pluck('triwulan');
            if ($sedangDipesan->isNotEmpty()) {
                throw new LanggananException('Triwulan ' . $sedangDipesan->unique()->sort()->map(fn($t) => "TW{$t}")->implode(', ') . ' sudah ada di invoice yang belum selesai. Selesaikan atau batalkan invoice itu dulu.');
            }

            $harga = $this->akses->hitungHarga($triwulan->count());
            $kodeUnik = $this->cariKodeUnik($harga['total']);

            $invoice = Invoice::create([
                'sekolah_id' => $sekolah->id,
                'subtotal' => $harga['subtotal'],
                'diskon' => $harga['diskon'],
                'total' => $harga['total'],
                'kode_unik' => $kodeUnik,
                'total_bayar' => $harga['total'] + $kodeUnik,
                'status' => Invoice::MENUNGGU_PEMBAYARAN,
                'metode' => 'manual',
                'jatuh_tempo_at' => now()->addDays((int) config('langganan.jatuh_tempo_hari')),
            ]);

            // nomor dibuat dari id supaya pasti unik tanpa perlu kunci tambahan
            $invoice->update(['nomor' => sprintf('INV-%d-%04d', now()->year, $invoice->id)]);

            $hargaSatuan = $this->akses->hargaTriwulan();
            foreach ($triwulan as $t) {
                $invoice->items()->create([
                    'tahun_anggaran_id' => $tahunAnggaran->id,
                    'triwulan' => $t,
                    'harga' => $hargaSatuan,
                ]);
            }

            return $invoice->load('items');
        });
    }

    /** Kode 3 digit acak yang membuat nominal akhir unik di antara invoice yang masih berjalan. */
    protected function cariKodeUnik(int $total): int
    {
        $min = (int) config('langganan.kode_unik_min');
        $max = (int) config('langganan.kode_unik_max');

        for ($i = 0; $i < 100; $i++) {
            $kode = random_int($min, $max);

            $bentrok = Invoice::whereIn('status', Invoice::PENDING)->where('total_bayar', $total + $kode)->exists();
            if (!$bentrok) {
                return $kode;
            }
        }

        throw new LanggananException('Gagal membuat kode unik pembayaran. Coba lagi beberapa saat.');
    }

    public function uploadBukti(Invoice $invoice, UploadedFile $file): Invoice
    {
        if (!$invoice->bisaUploadBukti()) {
            throw new LanggananException('Bukti transfer tidak bisa diunggah untuk invoice ini (sudah lewat jatuh tempo atau sudah diproses).');
        }

        $pathBaru = $file->store('bukti-transfer', 'public');

        if ($invoice->bukti_transfer) {
            Storage::disk('public')->delete($invoice->bukti_transfer);
        }

        $invoice->update([
            'bukti_transfer' => $pathBaru,
            'status' => Invoice::MENUNGGU_VERIFIKASI,
            'dibayar_at' => now(),
            'catatan_admin' => null,
        ]);

        return $invoice;
    }

    public function batalkan(Invoice $invoice): Invoice
    {
        if (!$invoice->bisaDibatalkan()) {
            throw new LanggananException('Invoice ini tidak bisa dibatalkan.');
        }

        $invoice->update(['status' => Invoice::DIBATALKAN]);

        return $invoice;
    }

    /**
     * Admin menyetujui pembayaran → hak triwulan dibuat/ditingkatkan jadi "berbayar" (tanpa batas waktu).
     * Dikunci (lockForUpdate) supaya klik ganda tidak memproses dua kali.
     */
    public function setujui(Invoice $invoice, User $admin): Invoice
    {
        $hasil = DB::transaction(function () use ($invoice, $admin) {
            $terkunci = Invoice::whereKey($invoice->getKey())->lockForUpdate()->firstOrFail();

            if (!$terkunci->bisaDisetujui()) {
                throw new LanggananException('Invoice ini sudah ' . strtolower($terkunci->labelStatus()) . ' dan tidak bisa disetujui.');
            }

            foreach ($terkunci->items as $item) {
                $hak = HakTriwulan::where('tahun_anggaran_id', $item->tahun_anggaran_id)->where('triwulan', $item->triwulan)->first();

                $data = [
                    'sumber' => 'berbayar',
                    'status' => 'aktif',
                    'berlaku_sampai' => null, // hak gratis yang punya batas waktu jadi permanen setelah dibayar
                    'invoice_item_id' => $item->id,
                ];

                if ($hak) {
                    $hak->update($data);
                } else {
                    HakTriwulan::create($data + [
                        'sekolah_id' => $terkunci->sekolah_id,
                        'tahun_anggaran_id' => $item->tahun_anggaran_id,
                        'triwulan' => $item->triwulan,
                    ]);
                }
            }

            $terkunci->update([
                'status' => Invoice::LUNAS,
                'diverifikasi_oleh' => $admin->id,
                'diverifikasi_at' => now(),
                'catatan_admin' => null,
            ]);

            return $terkunci;
        });

        // Email dikirim SETELAH transaksi database selesai, dan kegagalannya tidak menggagalkan persetujuan
        // (LanggananNotifier menangkap semua error). Kalau emailnya dikirim di dalam transaksi lalu transaksi
        // dibatalkan, sekolah bisa dapat email "sudah disetujui" padahal datanya tidak tersimpan.
        $this->notifier->invoiceDisetujui($hasil);

        return $hasil;
    }

    /** Admin menolak bukti: sekolah diberi waktu tambahan (jatuh tempo diperpanjang) untuk upload ulang. */
    public function tolak(Invoice $invoice, User $admin, string $alasan): Invoice
    {
        if (!$invoice->bisaDitolak()) {
            throw new LanggananException('Invoice ini tidak bisa ditolak.');
        }

        $invoice->update([
            'status' => Invoice::DITOLAK,
            'catatan_admin' => $alasan,
            'diverifikasi_oleh' => $admin->id,
            'diverifikasi_at' => now(),
            'jatuh_tempo_at' => now()->addDays((int) config('langganan.jatuh_tempo_hari')),
        ]);

        $this->notifier->invoiceDitolak($invoice);

        return $invoice;
    }

    /** Tandai invoice yang lewat jatuh tempo tanpa bukti sebagai kedaluwarsa. Dipanggil terjadwal. */
    public function kedaluwarsakan(): int
    {
        return Invoice::where('status', Invoice::MENUNGGU_PEMBAYARAN)
            ->where('jatuh_tempo_at', '<', now())
            ->update(['status' => Invoice::KEDALUWARSA]);
    }
}
