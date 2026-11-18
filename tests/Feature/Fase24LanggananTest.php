<?php

namespace Tests\Feature;

use App\Exceptions\LanggananException;
use App\Filament\Resources\InvoiceResource\Pages\ListInvoices;
use App\Filament\Resources\PaketLanggananResource\Pages\EditPaketLangganan;
use App\Models\HakTriwulan;
use App\Models\Invoice;
use App\Models\MasterBarang;
use App\Models\PaketLangganan;
use App\Models\Pegawai;
use App\Models\Sekolah;
use App\Models\TahunAnggaran;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\AksesTriwulanService;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Livewire\Volt\Volt;
use Tests\TestCase;

/**
 * Fase 24 — Langganan triwulan (manual): harga, pengunci generate, kuota percobaan,
 * siklus invoice, halaman sekolah, dan aksi admin (Filament).
 */
class Fase24LanggananTest extends TestCase
{
    use RefreshDatabase;

    protected Sekolah $sekolah;
    protected TahunAnggaran $ta;
    protected User $user;
    protected User $admin;
    protected Pegawai $pegawai;
    protected MasterBarang $barang;
    protected int $tahun;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sekolah = Sekolah::create([
            'nama_sekolah' => 'SDN 3 Rangkasbitung Timur',
            'kode_sekolah' => 'SDN3RKST',
            'npsn' => '20601936',
            'nama_pemerintah' => 'PEMKAB LEBAK',
            'nama_dinas' => 'DISDIK',
            'alamat' => 'Jl. Contoh',
            'tempat' => 'Rangkasbitung',
        ]);
        $this->ta = $this->sekolah->tahunAnggaran()->first();
        $this->tahun = (int) $this->ta->tahun;

        $this->user = User::factory()->create(['sekolah_id' => $this->sekolah->id]);
        $this->admin = User::factory()->create(['role' => 'admin']);

        $this->barang = MasterBarang::create([
            'sekolah_id' => $this->sekolah->id,
            'kode_barang' => 'ATK-001',
            'nama_barang' => 'Kertas HVS',
            'satuan_default' => 'Rim',
        ]);
        $this->pegawai = Pegawai::create([
            'sekolah_id' => $this->sekolah->id,
            'nama' => 'Budi',
            'nip' => '123',
            'jabatan' => 'Guru',
            'kategori' => 'guru',
        ]);
    }

    protected function pengunciAktif(): void
    {
        config(['langganan.pengunci_aktif' => true]);
    }

    protected function buatTransaksi(string $ref, string $tanggal): Transaksi
    {
        $t = Transaksi::create([
            'sekolah_id' => $this->sekolah->id,
            'nomor_referensi_asal' => $ref,
            'tanggal_npb' => $tanggal,
            'pihak_peminta_id' => $this->pegawai->id,
            'status' => 'draft',
        ]);
        $t->items()->create([
            'master_barang_id' => $this->barang->id,
            'spesifikasi' => 'Standar',
            'jumlah' => 1,
            'satuan' => 'Rim',
            'keperluan' => 'x',
        ]);

        return $t;
    }

    protected function beriHak(int $triwulan, array $override = []): HakTriwulan
    {
        return HakTriwulan::create($override + [
            'sekolah_id' => $this->sekolah->id,
            'tahun_anggaran_id' => $this->ta->id,
            'triwulan' => $triwulan,
            'sumber' => 'berbayar',
            'status' => 'aktif',
        ]);
    }

    protected function service(): InvoiceService
    {
        return app(InvoiceService::class);
    }

    // ===== Harga =====

    public function test_harga_katalog_awal_dan_aturan_min(): void
    {
        $akses = app(AksesTriwulanService::class);

        $this->assertSame(39000, $akses->hargaTriwulan());
        $this->assertSame(129000, $akses->hargaTahunan());

        $this->assertSame(['subtotal' => 0, 'diskon' => 0, 'total' => 0], $akses->hitungHarga(0));
        $this->assertSame(['subtotal' => 39000, 'diskon' => 0, 'total' => 39000], $akses->hitungHarga(1));
        $this->assertSame(['subtotal' => 117000, 'diskon' => 0, 'total' => 117000], $akses->hitungHarga(3));
        $this->assertSame(['subtotal' => 156000, 'diskon' => 27000, 'total' => 129000], $akses->hitungHarga(4));
    }

    // ===== Pengunci generate =====

    public function test_saklar_mati_membuat_generate_bebas_dan_kuota_tidak_terpakai(): void
    {
        $t = $this->buatTransaksi('REF-1', "{$this->tahun}-02-10");

        Volt::actingAs($this->user)->test('pages.transaksi.index')->call('generate', $t->id);

        $this->assertSame('selesai', $t->fresh()->status);
        $this->assertSame(0, (int) $this->sekolah->fresh()->generate_percobaan_terpakai);
    }

    public function test_tanpa_hak_generate_memakai_kuota_percobaan(): void
    {
        $this->pengunciAktif();
        $t = $this->buatTransaksi('REF-1', "{$this->tahun}-02-10");

        Volt::actingAs($this->user)->test('pages.transaksi.index')
            ->call('generate', $t->id)
            ->assertSet('errorMsg', null);

        $this->assertSame('selesai', $t->fresh()->status);
        $this->assertSame(1, (int) $this->sekolah->fresh()->generate_percobaan_terpakai);
    }

    public function test_kuota_habis_dan_tanpa_hak_generate_ditolak_dengan_tautan_beli(): void
    {
        $this->pengunciAktif();
        $this->sekolah->forceFill(['generate_percobaan_terpakai' => 3])->save();
        $t = $this->buatTransaksi('REF-1', "{$this->tahun}-02-10");

        Volt::actingAs($this->user)->test('pages.transaksi.index')
            ->call('generate', $t->id)
            ->assertSet('perluBeli', true)
            ->assertSee('Kuota percobaan gratis sudah habis')
            ->assertSee('Beli paket di menu')
            ->assertSee('TW1');

        $this->assertSame('draft', $t->fresh()->status);
        $this->assertNull($t->fresh()->nomor_npb);
    }

    public function test_hak_aktif_membuat_generate_lolos_tanpa_memakai_kuota(): void
    {
        $this->pengunciAktif();
        $this->sekolah->forceFill(['generate_percobaan_terpakai' => 3])->save();
        $this->beriHak(1);
        $t = $this->buatTransaksi('REF-1', "{$this->tahun}-02-10"); // TW1

        Volt::actingAs($this->user)->test('pages.transaksi.index')->call('generate', $t->id);

        $this->assertSame('selesai', $t->fresh()->status);
        $this->assertSame(3, (int) $this->sekolah->fresh()->generate_percobaan_terpakai);
    }

    public function test_hak_triwulan_lain_tidak_membuka_triwulan_ini(): void
    {
        $this->pengunciAktif();
        $this->sekolah->forceFill(['generate_percobaan_terpakai' => 3])->save();
        $this->beriHak(1); // hanya TW1
        $tw3 = $this->buatTransaksi('REF-TW3', "{$this->tahun}-08-10");

        Volt::actingAs($this->user)->test('pages.transaksi.index')
            ->call('generate', $tw3->id)
            ->assertSet('perluBeli', true);

        $this->assertSame('draft', $tw3->fresh()->status);
    }

    public function test_hak_gratis_yang_sudah_lewat_batas_dianggap_tidak_ada(): void
    {
        $this->pengunciAktif();
        $this->sekolah->forceFill(['generate_percobaan_terpakai' => 3])->save();
        $this->beriHak(1, ['sumber' => 'gratis', 'berlaku_sampai' => now()->subDay()->toDateString()]);
        $t = $this->buatTransaksi('REF-1', "{$this->tahun}-02-10");

        Volt::actingAs($this->user)->test('pages.transaksi.index')
            ->call('generate', $t->id)
            ->assertSet('perluBeli', true);
    }

    public function test_hak_dicabut_tidak_berlaku(): void
    {
        $this->pengunciAktif();
        $this->sekolah->forceFill(['generate_percobaan_terpakai' => 3])->save();
        $this->beriHak(1, ['status' => 'dicabut']);
        $t = $this->buatTransaksi('REF-1', "{$this->tahun}-02-10");

        Volt::actingAs($this->user)->test('pages.transaksi.index')
            ->call('generate', $t->id)
            ->assertSet('perluBeli', true);
    }

    public function test_bulk_generate_dibatalkan_total_kalau_kuota_tidak_cukup(): void
    {
        $this->pengunciAktif();
        $this->sekolah->forceFill(['generate_percobaan_terpakai' => 2])->save(); // sisa 1
        $a = $this->buatTransaksi('REF-A', "{$this->tahun}-02-10");
        $b = $this->buatTransaksi('REF-B', "{$this->tahun}-02-11");

        Volt::actingAs($this->user)->test('pages.transaksi.index')
            ->set('selected', [$a->id, $b->id])
            ->call('generateBulk')
            ->assertSet('perluBeli', true);

        $this->assertSame('draft', $a->fresh()->status);
        $this->assertSame('draft', $b->fresh()->status);
        $this->assertSame(2, (int) $this->sekolah->fresh()->generate_percobaan_terpakai);
    }

    public function test_bulk_generate_campuran_hak_dan_kuota(): void
    {
        $this->pengunciAktif();
        $this->beriHak(1);
        $tw1 = $this->buatTransaksi('REF-TW1', "{$this->tahun}-02-10"); // punya hak
        $tw3 = $this->buatTransaksi('REF-TW3', "{$this->tahun}-08-10"); // pakai kuota

        Volt::actingAs($this->user)->test('pages.transaksi.index')
            ->set('selected', [$tw1->id, $tw3->id])
            ->call('generateBulk')
            ->assertSet('errorMsg', null);

        $this->assertSame('selesai', $tw1->fresh()->status);
        $this->assertSame('selesai', $tw3->fresh()->status);
        $this->assertSame(1, (int) $this->sekolah->fresh()->generate_percobaan_terpakai); // hanya TW3
    }

    public function test_batal_generate_tidak_mengembalikan_kuota(): void
    {
        $this->pengunciAktif();
        $t = $this->buatTransaksi('REF-1', "{$this->tahun}-02-10");

        $komponen = Volt::actingAs($this->user)->test('pages.transaksi.index');
        $komponen->call('generate', $t->id);
        $this->assertSame(1, (int) $this->sekolah->fresh()->generate_percobaan_terpakai);

        $komponen->call('mintaBatalkanNomor', $t->id)->call('eksekusiBatalkanNomor');

        $this->assertSame('draft', $t->fresh()->status);
        $this->assertSame(1, (int) $this->sekolah->fresh()->generate_percobaan_terpakai);
    }

    public function test_unduh_word_tidak_dikunci_oleh_langganan(): void
    {
        $this->pengunciAktif();
        $t = $this->buatTransaksi('REF-1', "{$this->tahun}-02-10");

        $komponen = Volt::actingAs($this->user)->test('pages.transaksi.index');
        $komponen->call('generate', $t->id);

        // kuota dihabiskan setelah generate; unduh tetap boleh karena sudah di-generate
        $this->sekolah->forceFill(['generate_percobaan_terpakai' => 3])->save();
        $komponen->call('unduhWord', $t->id)->assertSet('errorMsg', null);
    }

    // ===== Invoice =====

    public function test_buat_invoice_satu_triwulan(): void
    {
        $invoice = $this->service()->buatInvoice($this->sekolah, $this->ta, [2]);

        $this->assertMatchesRegularExpression('/^INV-\d{4}-\d{4}$/', $invoice->nomor);
        $this->assertSame(Invoice::MENUNGGU_PEMBAYARAN, $invoice->status);
        $this->assertSame(39000, $invoice->total);
        $this->assertSame(0, $invoice->diskon);
        $this->assertGreaterThanOrEqual(1, $invoice->kode_unik);
        $this->assertLessThanOrEqual(999, $invoice->kode_unik);
        $this->assertSame(39000 + $invoice->kode_unik, $invoice->total_bayar);
        $this->assertCount(1, $invoice->items);
        $this->assertSame(2, $invoice->items->first()->triwulan);
        $this->assertTrue($invoice->jatuh_tempo_at->between(now()->addDays(6), now()->addDays(8)));
    }

    public function test_beli_empat_triwulan_dapat_harga_tahunan(): void
    {
        $invoice = $this->service()->buatInvoice($this->sekolah, $this->ta, [1, 2, 3, 4]);

        $this->assertSame(156000, $invoice->subtotal);
        $this->assertSame(27000, $invoice->diskon);
        $this->assertSame(129000, $invoice->total);
    }

    public function test_triwulan_duplikat_di_input_dihitung_sekali(): void
    {
        $invoice = $this->service()->buatInvoice($this->sekolah, $this->ta, [1, '1', 1]);

        $this->assertCount(1, $invoice->items);
        $this->assertSame(39000, $invoice->total);
    }

    public function test_invoice_ditolak_kalau_tidak_ada_triwulan_atau_di_luar_1_sampai_4(): void
    {
        try {
            $this->service()->buatInvoice($this->sekolah, $this->ta, []);
            $this->fail('Harusnya melempar LanggananException');
        } catch (LanggananException $e) {
            $this->assertSame('Pilih minimal 1 triwulan.', $e->getMessage());
        }

        $this->expectException(LanggananException::class);
        $this->service()->buatInvoice($this->sekolah, $this->ta, [5]);
    }

    public function test_tidak_bisa_beli_triwulan_yang_sudah_punya_hak(): void
    {
        $this->beriHak(1);

        $this->expectException(LanggananException::class);
        $this->expectExceptionMessage('TW1 sudah dibeli atau masih gratis');
        $this->service()->buatInvoice($this->sekolah, $this->ta, [1, 2]);
    }

    public function test_tidak_bisa_memesan_triwulan_yang_sudah_ada_di_invoice_berjalan(): void
    {
        $this->service()->buatInvoice($this->sekolah, $this->ta, [1, 2]);

        $this->expectException(LanggananException::class);
        $this->expectExceptionMessage('TW2');
        $this->service()->buatInvoice($this->sekolah, $this->ta, [2, 3]);
    }

    public function test_invoice_yang_dibatalkan_membebaskan_triwulannya(): void
    {
        $invoice = $this->service()->buatInvoice($this->sekolah, $this->ta, [1]);
        $this->service()->batalkan($invoice);

        $baru = $this->service()->buatInvoice($this->sekolah, $this->ta, [1]);

        $this->assertNotSame($invoice->id, $baru->id);
    }

    public function test_tidak_bisa_membuat_invoice_untuk_tahun_anggaran_sekolah_lain(): void
    {
        $lain = Sekolah::create([
            'nama_sekolah' => 'SDN Lain', 'kode_sekolah' => 'LAIN', 'npsn' => '20600001',
            'nama_pemerintah' => 'X', 'nama_dinas' => 'Y', 'alamat' => 'Z', 'tempat' => 'W',
        ]);

        $this->expectException(LanggananException::class);
        $this->service()->buatInvoice($this->sekolah, $lain->tahunAnggaran()->first(), [1]);
    }

    public function test_nominal_akhir_dua_invoice_berjalan_tidak_pernah_sama(): void
    {
        $lain = Sekolah::create([
            'nama_sekolah' => 'SDN Lain', 'kode_sekolah' => 'LAIN', 'npsn' => '20600001',
            'nama_pemerintah' => 'X', 'nama_dinas' => 'Y', 'alamat' => 'Z', 'tempat' => 'W',
        ]);

        $nominal = [];
        for ($i = 0; $i < 15; $i++) {
            $sekolah = $i % 2 === 0 ? $this->sekolah : $lain;
            $ta = $sekolah->tahunAnggaran()->first();
            // pakai triwulan berbeda supaya tidak kena aturan "sudah dipesan"
            $nominal[] = $this->service()->buatInvoice($sekolah, $ta, [($i % 4) + 1])->total_bayar;
            if ($i % 4 === 3) {
                // kosongkan pesanan supaya loop bisa lanjut memesan triwulan yang sama
                Invoice::whereIn('status', Invoice::PENDING)->update(['status' => Invoice::DIBATALKAN]);
            }
        }

        // semua invoice yang SEDANG berjalan di tiap momen punya nominal unik; di sini cukup
        // memastikan tidak ada nominal kembar di antara invoice pending yang tersisa
        $pending = Invoice::whereIn('status', Invoice::PENDING)->pluck('total_bayar');
        $this->assertSame($pending->count(), $pending->unique()->count());
    }

    public function test_upload_bukti_mengubah_status_dan_menyimpan_file(): void
    {
        Storage::fake('public');
        $invoice = $this->service()->buatInvoice($this->sekolah, $this->ta, [1]);

        $this->service()->uploadBukti($invoice, UploadedFile::fake()->image('bukti.jpg'));

        $invoice->refresh();
        $this->assertSame(Invoice::MENUNGGU_VERIFIKASI, $invoice->status);
        $this->assertNotNull($invoice->dibayar_at);
        Storage::disk('public')->assertExists($invoice->bukti_transfer);
    }

    public function test_upload_bukti_ditolak_untuk_invoice_kedaluwarsa(): void
    {
        Storage::fake('public');
        $invoice = $this->service()->buatInvoice($this->sekolah, $this->ta, [1]);
        $invoice->update(['jatuh_tempo_at' => now()->subHour()]);

        $this->expectException(LanggananException::class);
        $this->service()->uploadBukti($invoice->fresh(), UploadedFile::fake()->image('bukti.jpg'));
    }

    public function test_setujui_membuat_hak_berbayar_untuk_semua_triwulan_di_invoice(): void
    {
        $invoice = $this->service()->buatInvoice($this->sekolah, $this->ta, [1, 3]);

        $this->service()->setujui($invoice, $this->admin);

        $invoice->refresh();
        $this->assertSame(Invoice::LUNAS, $invoice->status);
        $this->assertSame($this->admin->id, $invoice->diverifikasi_oleh);
        $this->assertNotNull($invoice->diverifikasi_at);

        foreach ([1, 3] as $tw) {
            $this->assertDatabaseHas('hak_triwulan', [
                'tahun_anggaran_id' => $this->ta->id,
                'triwulan' => $tw,
                'sumber' => 'berbayar',
                'status' => 'aktif',
                'berlaku_sampai' => null,
            ]);
        }
        $this->assertDatabaseMissing('hak_triwulan', ['tahun_anggaran_id' => $this->ta->id, 'triwulan' => 2]);
    }

    public function test_setujui_meningkatkan_hak_gratis_berbatas_waktu_menjadi_permanen(): void
    {
        $this->beriHak(2, ['sumber' => 'gratis', 'berlaku_sampai' => now()->subDays(3)->toDateString()]);
        // hak gratis sudah lewat batas -> boleh dibeli
        $invoice = $this->service()->buatInvoice($this->sekolah, $this->ta, [2]);

        $this->service()->setujui($invoice, $this->admin);

        $hak = HakTriwulan::where('tahun_anggaran_id', $this->ta->id)->where('triwulan', 2)->first();
        $this->assertSame('berbayar', $hak->sumber);
        $this->assertNull($hak->berlaku_sampai);
        $this->assertSame(1, HakTriwulan::where('tahun_anggaran_id', $this->ta->id)->where('triwulan', 2)->count());
    }

    public function test_setujui_dua_kali_ditolak(): void
    {
        $invoice = $this->service()->buatInvoice($this->sekolah, $this->ta, [1]);
        $this->service()->setujui($invoice, $this->admin);

        $this->expectException(LanggananException::class);
        $this->service()->setujui($invoice->fresh(), $this->admin);
    }

    public function test_tolak_menyimpan_alasan_dan_memperpanjang_jatuh_tempo_lalu_boleh_upload_ulang(): void
    {
        Storage::fake('public');
        $invoice = $this->service()->buatInvoice($this->sekolah, $this->ta, [1]);
        $this->service()->uploadBukti($invoice, UploadedFile::fake()->image('salah.jpg'));
        $invoice->update(['jatuh_tempo_at' => now()->subDay()]); // sudah lewat jatuh tempo

        $this->service()->tolak($invoice->fresh(), $this->admin, 'Nominal tidak sesuai');

        $invoice->refresh();
        $this->assertSame(Invoice::DITOLAK, $invoice->status);
        $this->assertSame('Nominal tidak sesuai', $invoice->catatan_admin);
        $this->assertTrue($invoice->jatuh_tempo_at->isFuture());

        $this->service()->uploadBukti($invoice, UploadedFile::fake()->image('benar.jpg'));
        $this->assertSame(Invoice::MENUNGGU_VERIFIKASI, $invoice->fresh()->status);
    }

    public function test_kedaluwarsakan_hanya_menyentuh_invoice_menunggu_pembayaran_yang_lewat_jatuh_tempo(): void
    {
        Storage::fake('public');
        $lewat = $this->service()->buatInvoice($this->sekolah, $this->ta, [1]);
        $lewat->update(['jatuh_tempo_at' => now()->subHour()]);

        $sudahUpload = $this->service()->buatInvoice($this->sekolah, $this->ta, [2]);
        $this->service()->uploadBukti($sudahUpload, UploadedFile::fake()->image('b.jpg'));
        $sudahUpload->update(['jatuh_tempo_at' => now()->subHour()]);

        $masihBerlaku = $this->service()->buatInvoice($this->sekolah, $this->ta, [3]);

        $this->artisan('langganan:kedaluwarsakan-invoice')->assertSuccessful();

        $this->assertSame(Invoice::KEDALUWARSA, $lewat->fresh()->status);
        $this->assertSame(Invoice::MENUNGGU_VERIFIKASI, $sudahUpload->fresh()->status);
        $this->assertSame(Invoice::MENUNGGU_PEMBAYARAN, $masihBerlaku->fresh()->status);
    }

    // ===== Halaman sekolah =====

    public function test_halaman_langganan_menampilkan_empat_kartu_dan_kuota(): void
    {
        $this->beriHak(1);

        Volt::actingAs($this->user)->test('pages.langganan.index')
            ->assertSee('Triwulan 1 (Jan–Mar)')
            ->assertSee('Triwulan 4 (Okt–Des)')
            ->assertSee('Sudah dibeli')
            ->assertSee('3 dari 3');
    }

    public function test_pilih_semua_sisa_hanya_mencentang_yang_belum_dibeli(): void
    {
        $this->beriHak(1);

        Volt::actingAs($this->user)->test('pages.langganan.index')
            ->call('pilihSemuaSisa')
            ->assertSet('dipilih', [2, 3, 4]);
    }

    public function test_buat_invoice_dari_halaman_langganan(): void
    {
        Volt::actingAs($this->user)->test('pages.langganan.index')
            ->set('dipilih', [1, 2])
            ->call('buatInvoice')
            ->assertSet('errorMsg', null);

        $invoice = Invoice::where('sekolah_id', $this->sekolah->id)->first();
        $this->assertNotNull($invoice);
        $this->assertSame(78000, $invoice->total);
        $this->assertCount(2, $invoice->items);
    }

    public function test_buat_invoice_tanpa_centang_menampilkan_error(): void
    {
        Volt::actingAs($this->user)->test('pages.langganan.index')
            ->call('buatInvoice')
            ->assertSet('errorMsg', 'Pilih minimal 1 triwulan.');
    }

    public function test_halaman_invoice_menampilkan_nominal_dan_rekening(): void
    {
        \App\Models\RekeningDonasi::create(['nama_bank' => 'BCA', 'nomor_rekening' => '1234567890', 'atas_nama' => 'Delix Studio', 'urutan' => 1]);
        $invoice = $this->service()->buatInvoice($this->sekolah, $this->ta, [1]);

        Volt::actingAs($this->user)->test('pages.langganan.invoice', ['invoice' => $invoice])
            ->assertSee($invoice->nomor)
            ->assertSee(number_format($invoice->total_bayar, 0, ',', '.'))
            ->assertSee('1234567890')
            ->assertSee('Delix Studio');
    }

    public function test_sekolah_lain_tidak_bisa_membuka_invoice(): void
    {
        $invoice = $this->service()->buatInvoice($this->sekolah, $this->ta, [1]);

        $lain = Sekolah::create([
            'nama_sekolah' => 'SDN Lain', 'kode_sekolah' => 'LAIN', 'npsn' => '20600001',
            'nama_pemerintah' => 'X', 'nama_dinas' => 'Y', 'alamat' => 'Z', 'tempat' => 'W',
        ]);
        $userLain = User::factory()->create(['sekolah_id' => $lain->id]);

        $this->actingAs($userLain)->get(route('langganan.invoice', $invoice))->assertNotFound();
        $this->actingAs($this->user)->get(route('langganan.invoice', $invoice))->assertOk();
    }

    public function test_kirim_bukti_dari_halaman_invoice(): void
    {
        Storage::fake('public');
        $invoice = $this->service()->buatInvoice($this->sekolah, $this->ta, [1]);

        Volt::actingAs($this->user)->test('pages.langganan.invoice', ['invoice' => $invoice])
            ->set('bukti', UploadedFile::fake()->image('bukti.jpg'))
            ->call('kirimBukti')
            ->assertHasNoErrors();

        $this->assertSame(Invoice::MENUNGGU_VERIFIKASI, $invoice->fresh()->status);
    }

    public function test_kirim_bukti_menolak_tipe_file_yang_salah(): void
    {
        Storage::fake('public');
        $invoice = $this->service()->buatInvoice($this->sekolah, $this->ta, [1]);

        Volt::actingAs($this->user)->test('pages.langganan.invoice', ['invoice' => $invoice])
            ->set('bukti', UploadedFile::fake()->create('virus.exe', 10))
            ->call('kirimBukti')
            ->assertHasErrors('bukti');

        $this->assertSame(Invoice::MENUNGGU_PEMBAYARAN, $invoice->fresh()->status);
    }

    public function test_batalkan_invoice_dari_halaman_invoice(): void
    {
        $invoice = $this->service()->buatInvoice($this->sekolah, $this->ta, [1]);

        Volt::actingAs($this->user)->test('pages.langganan.invoice', ['invoice' => $invoice])
            ->call('batalkan');

        $this->assertSame(Invoice::DIBATALKAN, $invoice->fresh()->status);
    }

    // ===== Admin (Filament) =====

    public function test_admin_menyetujui_invoice_lewat_tabel(): void
    {
        $invoice = $this->service()->buatInvoice($this->sekolah, $this->ta, [1]);

        Livewire::actingAs($this->admin)
            ->test(ListInvoices::class)
            ->assertCanSeeTableRecords([$invoice])
            ->callTableAction('setujui', $invoice);

        $this->assertSame(Invoice::LUNAS, $invoice->fresh()->status);
        $this->assertDatabaseHas('hak_triwulan', ['tahun_anggaran_id' => $this->ta->id, 'triwulan' => 1, 'sumber' => 'berbayar']);
    }

    public function test_admin_menolak_invoice_dengan_alasan(): void
    {
        $invoice = $this->service()->buatInvoice($this->sekolah, $this->ta, [1]);

        Livewire::actingAs($this->admin)
            ->test(ListInvoices::class)
            ->callTableAction('tolak', $invoice, data: ['alasan' => 'Bukti buram']);

        $this->assertSame(Invoice::DITOLAK, $invoice->fresh()->status);
        $this->assertSame('Bukti buram', $invoice->fresh()->catatan_admin);
    }

    public function test_admin_wajib_mengisi_alasan_saat_menolak(): void
    {
        $invoice = $this->service()->buatInvoice($this->sekolah, $this->ta, [1]);

        Livewire::actingAs($this->admin)
            ->test(ListInvoices::class)
            ->callTableAction('tolak', $invoice, data: ['alasan' => ''])
            ->assertHasTableActionErrors(['alasan' => 'required']);

        $this->assertSame(Invoice::MENUNGGU_PEMBAYARAN, $invoice->fresh()->status);
    }

    public function test_admin_mengubah_harga_paket(): void
    {
        $paket = PaketLangganan::where('kode', 'triwulan')->first();

        Livewire::actingAs($this->admin)
            ->test(EditPaketLangganan::class, ['record' => $paket->getKey()])
            ->fillForm(['harga' => 45000])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(45000, PaketLangganan::harga('triwulan'));
        $this->assertSame(45000, app(AksesTriwulanService::class)->hitungHarga(1)['total']);
    }

    public function test_menu_langganan_sekolah_hanya_muncul_saat_pengunci_aktif(): void
    {
        $this->actingAs($this->user)->get(route('dashboard'))->assertDontSee(route('langganan.index'));

        $this->pengunciAktif();
        $this->actingAs($this->user)->get(route('dashboard'))->assertSee(route('langganan.index'));
    }
}
