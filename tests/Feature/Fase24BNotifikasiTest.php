<?php

namespace Tests\Feature;

use App\Mail\NotifikasiLanggananMail;
use App\Models\HakTriwulan;
use App\Models\Invoice;
use App\Models\MasterBarang;
use App\Models\Pegawai;
use App\Models\Sekolah;
use App\Models\TahunAnggaran;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Fase 24B — kwitansi PDF, email notifikasi, pengingat terjadwal, dan pembersihan PDF surat.
 */
class Fase24BNotifikasiTest extends TestCase
{
    use RefreshDatabase;

    protected Sekolah $sekolah;
    protected TahunAnggaran $ta;
    protected User $user;
    protected User $admin;
    protected int $tahun;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sekolah = $this->buatSekolah('SDN 3 Rangkasbitung Timur', 'SDN3RKST', '20601936');
        $this->ta = $this->sekolah->tahunAnggaran()->first();
        $this->tahun = (int) $this->ta->tahun;
        $this->user = User::factory()->create(['sekolah_id' => $this->sekolah->id]);
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    protected function buatSekolah(string $nama, string $kode, string $npsn): Sekolah
    {
        return Sekolah::create([
            'nama_sekolah' => $nama, 'kode_sekolah' => $kode, 'npsn' => $npsn,
            'nama_pemerintah' => 'PEMKAB LEBAK', 'nama_dinas' => 'DISDIK', 'alamat' => 'Jl. Contoh', 'tempat' => 'Rangkasbitung',
        ]);
    }

    protected function service(): InvoiceService
    {
        return app(InvoiceService::class);
    }

    protected function invoiceLunas(array $triwulan = [1]): Invoice
    {
        Mail::fake();
        $invoice = $this->service()->buatInvoice($this->sekolah, $this->ta, $triwulan);

        return $this->service()->setujui($invoice, $this->admin);
    }

    // ===== Kwitansi PDF =====

    public function test_sekolah_bisa_unduh_kwitansi_invoice_lunas_sebagai_pdf(): void
    {
        $invoice = $this->invoiceLunas([1, 2]);

        $respons = $this->actingAs($this->user)->get(route('langganan.kwitansi', $invoice));

        $respons->assertOk();
        $respons->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $respons->getContent());
        $this->assertStringContainsString('Kwitansi-KWT-', $respons->headers->get('Content-Disposition'));
    }

    public function test_kwitansi_ditolak_untuk_invoice_yang_belum_lunas(): void
    {
        $invoice = $this->service()->buatInvoice($this->sekolah, $this->ta, [1]);

        $this->actingAs($this->user)->get(route('langganan.kwitansi', $invoice))->assertNotFound();
    }

    public function test_kwitansi_sekolah_lain_tidak_bisa_diunduh(): void
    {
        $invoice = $this->invoiceLunas();
        $lain = $this->buatSekolah('SDN Lain', 'LAIN', '20600001');
        $userLain = User::factory()->create(['sekolah_id' => $lain->id]);

        $this->actingAs($userLain)->get(route('langganan.kwitansi', $invoice))->assertNotFound();
    }

    public function test_tamu_tidak_bisa_unduh_kwitansi(): void
    {
        $invoice = $this->invoiceLunas();

        $this->get(route('langganan.kwitansi', $invoice))->assertRedirect(route('login'));
    }

    public function test_tombol_kwitansi_tampil_di_halaman_invoice_lunas(): void
    {
        $invoice = $this->invoiceLunas();

        \Livewire\Volt\Volt::actingAs($this->user)->test('pages.langganan.invoice', ['invoice' => $invoice])
            ->assertSee('Unduh Kwitansi (PDF)');
    }

    // ===== Email =====

    public function test_setujui_mengirim_email_dengan_lampiran_kwitansi(): void
    {
        Mail::fake();
        $invoice = $this->service()->buatInvoice($this->sekolah, $this->ta, [1, 3]);

        $this->service()->setujui($invoice, $this->admin);

        Mail::assertSent(NotifikasiLanggananMail::class, function (NotifikasiLanggananMail $mail) {
            return $mail->hasTo($this->user->email)
                && $mail->tampilan === 'disetujui'
                && $mail->lampiranPdf !== null
                && str_starts_with($mail->lampiranPdf, '%PDF')
                && str_contains($mail->namaLampiran, 'KWT-');
        });
    }

    public function test_tolak_mengirim_email_berisi_alasan(): void
    {
        Mail::fake();
        $invoice = $this->service()->buatInvoice($this->sekolah, $this->ta, [1]);

        $this->service()->tolak($invoice, $this->admin, 'Nominal tidak sesuai');

        Mail::assertSent(NotifikasiLanggananMail::class, fn($mail) => $mail->hasTo($this->user->email)
            && $mail->tampilan === 'ditolak'
            && $mail->data['alasan'] === 'Nominal tidak sesuai');
    }

    public function test_email_dirender_tanpa_error(): void
    {
        $invoice = $this->invoiceLunas();
        $notifier = app(\App\Services\LanggananNotifier::class);

        $html = (new NotifikasiLanggananMail('Tes', 'disetujui', [
            'nomorInvoice' => $invoice->nomor, 'totalBayar' => '39.017', 'daftarTriwulan' => 'TW1',
            'invoiceUrl' => route('langganan.invoice', $invoice),
        ]))->render();

        $this->assertStringContainsString($invoice->nomor, $html);
        $this->assertStringContainsString('Buka Invoice', $html);
        $this->assertNotNull($notifier);
    }

    public function test_kegagalan_email_tidak_menggagalkan_persetujuan(): void
    {
        $invoice = $this->service()->buatInvoice($this->sekolah, $this->ta, [1]);

        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP down'));

        $hasil = $this->service()->setujui($invoice, $this->admin);

        $this->assertSame(Invoice::LUNAS, $hasil->status);
        $this->assertDatabaseHas('hak_triwulan', ['tahun_anggaran_id' => $this->ta->id, 'triwulan' => 1, 'status' => 'aktif']);
    }

    // ===== Pengingat =====

    public function test_pengingat_invoice_hampir_kedaluwarsa_hanya_untuk_jatuh_tempo_2_hari_lagi(): void
    {
        Mail::fake();
        $dua = $this->service()->buatInvoice($this->sekolah, $this->ta, [1]);
        $dua->update(['jatuh_tempo_at' => now()->addDays(2)->setTime(15, 0)]);
        $lima = $this->service()->buatInvoice($this->sekolah, $this->ta, [2]);
        $lima->update(['jatuh_tempo_at' => now()->addDays(5)]);

        $this->artisan('langganan:kirim-pengingat')->assertSuccessful();

        Mail::assertSent(NotifikasiLanggananMail::class, 1);
        Mail::assertSent(NotifikasiLanggananMail::class, fn($m) => $m->tampilan === 'hampir-kedaluwarsa' && $m->data['nomorInvoice'] === $dua->nomor);
    }

    public function test_pengingat_admin_untuk_invoice_menggantung_lebih_dari_24_jam(): void
    {
        Mail::fake();
        Storage::fake('public');

        $lama = $this->service()->buatInvoice($this->sekolah, $this->ta, [1]);
        $this->service()->uploadBukti($lama, UploadedFile::fake()->image('a.jpg'));
        $lama->update(['dibayar_at' => now()->subDays(2)]);

        $baru = $this->service()->buatInvoice($this->sekolah, $this->ta, [2]);
        $this->service()->uploadBukti($baru, UploadedFile::fake()->image('b.jpg'));

        $this->artisan('langganan:kirim-pengingat')->assertSuccessful();

        Mail::assertSent(NotifikasiLanggananMail::class, function ($m) use ($lama) {
            return $m->tampilan === 'admin-menunggu-verifikasi'
                && $m->hasTo($this->admin->email)
                && count($m->data['daftar']) === 1
                && $m->data['daftar'][0]['nomor'] === $lama->nomor
                && $m->data['daftar'][0]['umur'] === '2 hari';
        });
    }

    public function test_tidak_ada_pengingat_admin_kalau_semua_invoice_masih_baru(): void
    {
        Mail::fake();
        Storage::fake('public');
        $invoice = $this->service()->buatInvoice($this->sekolah, $this->ta, [1]);
        $this->service()->uploadBukti($invoice, UploadedFile::fake()->image('a.jpg'));

        $this->artisan('langganan:kirim-pengingat')->assertSuccessful();

        Mail::assertNothingSent();
    }

    protected function buatTransaksiDraft(string $tanggal): Transaksi
    {
        $barang = MasterBarang::firstOrCreate(
            ['sekolah_id' => $this->sekolah->id, 'kode_barang' => 'ATK-001'],
            ['nama_barang' => 'Kertas HVS', 'satuan_default' => 'Rim'],
        );
        $pegawai = Pegawai::firstOrCreate(
            ['sekolah_id' => $this->sekolah->id, 'nip' => '123'],
            ['nama' => 'Budi', 'jabatan' => 'Guru', 'kategori' => 'guru'],
        );

        $t = Transaksi::create([
            'sekolah_id' => $this->sekolah->id,
            'nomor_referensi_asal' => 'REF-' . $tanggal,
            'tanggal_npb' => $tanggal,
            'pihak_peminta_id' => $pegawai->id,
            'status' => 'draft',
        ]);
        $t->items()->create(['master_barang_id' => $barang->id, 'spesifikasi' => 'S', 'jumlah' => 1, 'satuan' => 'Rim', 'keperluan' => 'x']);

        return $t;
    }

    public function test_pengingat_draft_dikirim_pada_1_april_untuk_triwulan_1(): void
    {
        Mail::fake();
        config(['langganan.pengunci_aktif' => true]);
        $this->buatTransaksiDraft("{$this->tahun}-02-10");
        $this->buatTransaksiDraft("{$this->tahun}-03-20");
        $this->buatTransaksiDraft("{$this->tahun}-05-02"); // TW2: tidak dihitung

        $this->artisan('langganan:kirim-pengingat', ['--tanggal' => "{$this->tahun}-04-01"])->assertSuccessful();

        Mail::assertSent(NotifikasiLanggananMail::class, fn($m) => $m->tampilan === 'draft-belum-digenerate'
            && $m->hasTo($this->user->email)
            && $m->data['jumlahDraft'] === 2
            && $m->data['namaTriwulan'] === "TW1 {$this->tahun}");
    }

    public function test_pengingat_draft_tidak_dikirim_di_tanggal_selain_awal_triwulan(): void
    {
        Mail::fake();
        config(['langganan.pengunci_aktif' => true]);
        $this->buatTransaksiDraft("{$this->tahun}-02-10");

        $this->artisan('langganan:kirim-pengingat', ['--tanggal' => "{$this->tahun}-04-02"])->assertSuccessful();

        Mail::assertNothingSent();
    }

    public function test_pengingat_draft_tidak_dikirim_kalau_triwulan_sudah_punya_hak_atau_saklar_mati(): void
    {
        Mail::fake();
        $this->buatTransaksiDraft("{$this->tahun}-02-10");

        // saklar mati
        $this->artisan('langganan:kirim-pengingat', ['--tanggal' => "{$this->tahun}-04-01"])->assertSuccessful();
        Mail::assertNothingSent();

        // saklar nyala tapi sudah punya hak TW1
        config(['langganan.pengunci_aktif' => true]);
        HakTriwulan::create([
            'sekolah_id' => $this->sekolah->id, 'tahun_anggaran_id' => $this->ta->id,
            'triwulan' => 1, 'sumber' => 'berbayar', 'status' => 'aktif',
        ]);
        $this->artisan('langganan:kirim-pengingat', ['--tanggal' => "{$this->tahun}-04-01"])->assertSuccessful();
        Mail::assertNothingSent();
    }

    public function test_command_menolak_format_tanggal_salah(): void
    {
        $this->artisan('langganan:kirim-pengingat', ['--tanggal' => '01-04-2027'])->assertFailed();
    }

    // ===== Pembersihan =====

    public function test_generator_pdf_surat_sudah_dihapus_dan_dompdf_tetap_dipakai_kwitansi(): void
    {
        // cek file, bukan class_exists(): classmap Composer yang di-cache bisa masih mengingat class yang sudah dihapus
        $this->assertFileDoesNotExist(app_path('Services/SuratPdfGenerator.php'));
        $this->assertFalse(view()->exists('pdf.surat'));
        $this->assertTrue(view()->exists('pdf.kwitansi'));
    }
}
