<?php

namespace Tests\Feature;

use App\Exceptions\LanggananException;
use App\Filament\Resources\HakTriwulanResource;
use App\Filament\Resources\HakTriwulanResource\Pages\ListHakTriwulans;
use App\Filament\Resources\SekolahResource;
use App\Filament\Resources\SekolahResource\Pages\ListSekolahs;
use App\Filament\Resources\SekolahResource\Pages\ViewSekolah;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Filament\Widgets\AdminStatsOverview;
use App\Filament\Widgets\BelumOnboardingWidget;
use App\Filament\Widgets\CalonPembeliWidget;
use App\Filament\Widgets\InvoiceMenungguVerifikasiWidget;
use App\Filament\Widgets\PendaftaranChart;
use App\Filament\Widgets\PendapatanChart;
use App\Models\HakTriwulan;
use App\Models\Invoice;
use App\Models\MasterBarang;
use App\Models\Pegawai;
use App\Models\Sekolah;
use App\Models\TahunAnggaran;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\AksesTriwulanService;
use App\Services\InvoiceService;
use App\Support\EksporCsv;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Fase 25 — Panel Admin v2: login terakhir, Sekolah, User (diperluas), Hak Triwulan, widget dashboard,
 * ekspor CSV, dan navigasi.
 */
class Fase25AdminV2Test extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Sekolah $sekolahA;
    protected Sekolah $sekolahB;
    protected TahunAnggaran $taA;
    protected User $userA1;
    protected User $userA2;
    protected User $userTanpaSekolah;
    protected int $tahun;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();

        $this->admin = User::factory()->create(['role' => 'admin']);

        $this->sekolahA = $this->buatSekolah('SDN A', 'SDNA', '20600011');
        $this->sekolahB = $this->buatSekolah('SDN B', 'SDNB', '20600022');
        $this->taA = $this->sekolahA->tahunAnggaran()->first();
        $this->tahun = (int) $this->taA->tahun;

        $this->userA1 = User::factory()->create([
            'sekolah_id' => $this->sekolahA->id, 'email' => 'a1@contoh.test',
            'google_id' => 'google-123', 'last_login_at' => now()->subDay(),
        ]);
        $this->userA2 = User::factory()->create(['sekolah_id' => $this->sekolahA->id, 'email' => 'a2@contoh.test']);
        $this->userTanpaSekolah = User::factory()->create(['sekolah_id' => null, 'role' => 'sekolah', 'email' => 'baru@contoh.test']);
    }

    protected function buatSekolah(string $nama, string $kode, string $npsn): Sekolah
    {
        return Sekolah::create([
            'nama_sekolah' => $nama, 'kode_sekolah' => $kode, 'npsn' => $npsn,
            'nama_pemerintah' => 'PEMKAB LEBAK', 'nama_dinas' => 'DISDIK', 'alamat' => 'Jl. Contoh', 'tempat' => 'Rangkasbitung',
        ]);
    }

    protected function buatTransaksi(Sekolah $sekolah, string $ref, string $status, string $tanggal): Transaksi
    {
        $barang = MasterBarang::firstOrCreate(
            ['sekolah_id' => $sekolah->id, 'kode_barang' => 'ATK-001'],
            ['nama_barang' => 'Kertas HVS', 'satuan_default' => 'Rim'],
        );
        $pegawai = Pegawai::firstOrCreate(
            ['sekolah_id' => $sekolah->id, 'nip' => '123'],
            ['nama' => 'Budi', 'jabatan' => 'Guru', 'kategori' => 'guru'],
        );
        $t = Transaksi::create([
            'sekolah_id' => $sekolah->id, 'nomor_referensi_asal' => $ref, 'tanggal_npb' => $tanggal,
            'pihak_peminta_id' => $pegawai->id, 'status' => $status,
        ]);
        $t->items()->create(['master_barang_id' => $barang->id, 'spesifikasi' => 'S', 'jumlah' => 1, 'satuan' => 'Rim', 'keperluan' => 'x']);

        return $t;
    }

    protected function isiDataSekolahA(): void
    {
        $this->buatTransaksi($this->sekolahA, 'A-1', 'draft', "{$this->tahun}-02-01");
        $this->buatTransaksi($this->sekolahA, 'A-2', 'draft', "{$this->tahun}-02-02");
        $this->buatTransaksi($this->sekolahA, 'A-3', 'selesai', "{$this->tahun}-02-03");
        HakTriwulan::create([
            'sekolah_id' => $this->sekolahA->id, 'tahun_anggaran_id' => $this->taA->id,
            'triwulan' => 1, 'sumber' => 'berbayar', 'status' => 'aktif',
        ]);
    }

    protected function service(): AksesTriwulanService
    {
        return app(AksesTriwulanService::class);
    }

    // ===== Login terakhir =====

    public function test_event_login_mencatat_waktu_login_terakhir(): void
    {
        $this->assertNull($this->userA2->last_login_at);

        event(new Login('web', $this->userA2, false));

        $this->assertNotNull($this->userA2->fresh()->last_login_at);
        $this->assertTrue($this->userA2->fresh()->last_login_at->diffInSeconds(now()) < 10);
    }

    // ===== Resource Sekolah =====

    public function test_tabel_sekolah_menampilkan_hitungan_aktivitas_dan_langganan(): void
    {
        $this->isiDataSekolahA();

        // assertTableColumnStateSet membaca nilai dari objek yang kita berikan, BUKAN dari query tabel.
        // Model biasa tidak punya atribut hasil withCount (users_count dst.), jadi ambil lewat query resource
        // yang sama dengan yang dipakai tabel.
        $a = SekolahResource::getEloquentQuery()->find($this->sekolahA->id);
        $b = SekolahResource::getEloquentQuery()->find($this->sekolahB->id);

        Livewire::actingAs($this->admin)
            ->test(ListSekolahs::class)
            ->assertCanSeeTableRecords([$this->sekolahA, $this->sekolahB])
            ->assertTableColumnStateSet('users_count', 2, $a)
            ->assertTableColumnStateSet('transaksi_count', 3, $a)
            ->assertTableColumnStateSet('draft_count', 2, $a)
            ->assertTableColumnStateSet('surat_digenerate_count', 1, $a)
            ->assertTableColumnStateSet('hak_aktif_count', 1, $a)
            ->assertTableColumnStateSet('users_count', 0, $b);
    }

    public function test_filter_sekolah_belum_pernah_generate_dan_kuota_habis(): void
    {
        $this->isiDataSekolahA(); // A sudah punya surat selesai
        $this->sekolahB->forceFill(['generate_percobaan_terpakai' => 3])->save();

        Livewire::actingAs($this->admin)
            ->test(ListSekolahs::class)
            ->filterTable('belum_pernah_generate')
            ->assertCanSeeTableRecords([$this->sekolahB])
            ->assertCanNotSeeTableRecords([$this->sekolahA])
            ->removeTableFilter('belum_pernah_generate')
            ->filterTable('kuota_habis')
            ->assertCanSeeTableRecords([$this->sekolahB])
            ->assertCanNotSeeTableRecords([$this->sekolahA]);
    }

    public function test_filter_sekolah_punya_triwulan_aktif(): void
    {
        $this->isiDataSekolahA();

        Livewire::actingAs($this->admin)
            ->test(ListSekolahs::class)
            ->filterTable('punya_hak_aktif')
            ->assertCanSeeTableRecords([$this->sekolahA])
            ->assertCanNotSeeTableRecords([$this->sekolahB]);
    }

    public function test_halaman_detail_sekolah_menampilkan_user_dan_hak(): void
    {
        $this->isiDataSekolahA();

        Livewire::actingAs($this->admin)
            ->test(ViewSekolah::class, ['record' => $this->sekolahA->getKey()])
            ->assertSee('SDN A')
            ->assertSee('20600011')
            ->assertSee('a1@contoh.test')
            ->assertSee('a2@contoh.test')
            ->assertSee('TW1');
    }

    public function test_admin_tidak_bisa_menghapus_atau_membuat_sekolah_dari_panel(): void
    {
        $this->assertFalse(SekolahResource::canCreate());
        $this->assertFalse(SekolahResource::canDeleteAny());
        $this->assertFalse(SekolahResource::canDelete($this->sekolahA));
    }

    // ===== Resource User (diperluas) =====

    public function test_tabel_user_menampilkan_sekolah_cara_daftar_dan_login_terakhir(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ListUsers::class)
            ->assertCanSeeTableRecords([$this->userA1, $this->userA2, $this->userTanpaSekolah])
            ->assertTableColumnStateSet('daftar_via', 'Google', $this->userA1)
            ->assertTableColumnStateSet('daftar_via', 'Email', $this->userA2)
            ->assertTableColumnStateSet('sekolah.nama_sekolah', 'SDN A', $this->userA1);
    }

    public function test_filter_user_belum_onboarding_dan_belum_pernah_login(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ListUsers::class)
            ->filterTable('belum_onboarding')
            ->assertCanSeeTableRecords([$this->userTanpaSekolah])
            ->assertCanNotSeeTableRecords([$this->userA1, $this->userA2])
            ->removeTableFilter('belum_onboarding')
            ->filterTable('belum_pernah_login')
            ->assertCanSeeTableRecords([$this->userA2, $this->userTanpaSekolah])
            ->assertCanNotSeeTableRecords([$this->userA1]);
    }

    public function test_pencarian_user_berdasarkan_nama_sekolah(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ListUsers::class)
            ->searchTable('SDN A')
            ->assertCanSeeTableRecords([$this->userA1, $this->userA2])
            ->assertCanNotSeeTableRecords([$this->userTanpaSekolah]);
    }

    // ===== Hak Triwulan: service =====

    public function test_admin_memberi_hak_manual_tanpa_batas_waktu(): void
    {
        $hak = $this->service()->berikanHak($this->sekolahA, $this->taA, 2, 'manual', null, 'sekolah percontohan');

        $this->assertSame('manual', $hak->sumber);
        $this->assertSame('aktif', $hak->status);
        $this->assertNull($hak->berlaku_sampai);
        $this->assertSame('sekolah percontohan', $hak->catatan);
    }

    public function test_hak_gratis_dengan_batas_waktu(): void
    {
        $hak = $this->service()->berikanHak($this->sekolahA, $this->taA, 3, 'gratis', now()->addMonth());

        $this->assertSame('gratis', $hak->sumber);
        $this->assertSame(now()->addMonth()->toDateString(), $hak->berlaku_sampai->toDateString());
    }

    public function test_memberi_hak_tidak_menimpa_hak_berbayar_aktif(): void
    {
        $this->isiDataSekolahA(); // TW1 berbayar aktif

        $this->expectException(LanggananException::class);
        $this->expectExceptionMessage('sudah dibeli');
        $this->service()->berikanHak($this->sekolahA, $this->taA, 1, 'manual');
    }

    public function test_memberi_hak_ulang_memperbarui_baris_yang_sama_bukan_membuat_baru(): void
    {
        $pertama = $this->service()->berikanHak($this->sekolahA, $this->taA, 2, 'gratis', now()->subDay());
        $kedua = $this->service()->berikanHak($this->sekolahA, $this->taA, 2, 'manual');

        $this->assertSame($pertama->id, $kedua->id);
        $this->assertSame(1, HakTriwulan::where('tahun_anggaran_id', $this->taA->id)->where('triwulan', 2)->count());
        $this->assertNull($kedua->fresh()->berlaku_sampai);
    }

    public function test_pemberian_hak_menolak_input_tidak_valid(): void
    {
        foreach ([[0, 'manual'], [5, 'manual'], [1, 'berbayar']] as [$tw, $sumber]) {
            try {
                $this->service()->berikanHak($this->sekolahA, $this->taA, $tw, $sumber);
                $this->fail("Seharusnya menolak triwulan {$tw} sumber {$sumber}");
            } catch (LanggananException $e) {
                $this->assertNotSame('', $e->getMessage());
            }
        }

        $this->expectException(LanggananException::class);
        $this->service()->berikanHak($this->sekolahB, $this->taA, 1, 'manual'); // tahun anggaran milik sekolah lain
    }

    public function test_cabut_dan_aktifkan_kembali_hak(): void
    {
        $hak = $this->service()->berikanHak($this->sekolahA, $this->taA, 2, 'manual');

        $this->service()->cabutHak($hak);
        $this->assertSame('dicabut', $hak->fresh()->status);
        $this->assertArrayNotHasKey($this->taA->id . '-2', $this->service()->hakAktifMap($this->sekolahA));

        $this->service()->aktifkanKembaliHak($hak);
        $this->assertSame('aktif', $hak->fresh()->status);
        $this->assertArrayHasKey($this->taA->id . '-2', $this->service()->hakAktifMap($this->sekolahA));
    }

    public function test_hak_manual_membuka_generate_di_triwulan_itu(): void
    {
        config(['langganan.pengunci_aktif' => true]);
        $this->sekolahA->forceFill(['generate_percobaan_terpakai' => 3])->save();
        $t = $this->buatTransaksi($this->sekolahA, 'A-1', 'draft', "{$this->tahun}-05-10"); // TW2
        $t->load('tahunAnggaran');

        $sebelum = $this->service()->periksaGenerate($this->sekolahA->fresh(), collect([$t]));
        $this->assertFalse($sebelum['boleh']);

        $this->service()->berikanHak($this->sekolahA, $this->taA, 2, 'manual');

        $sesudah = $this->service()->periksaGenerate($this->sekolahA->fresh(), collect([$t]));
        $this->assertTrue($sesudah['boleh']);
        $this->assertSame(0, $sesudah['pakai_kuota']);
    }

    // ===== Hak Triwulan: panel =====

    public function test_tabel_hak_triwulan_dan_aksi_cabut_aktifkan(): void
    {
        $hak = $this->service()->berikanHak($this->sekolahA, $this->taA, 2, 'manual');

        Livewire::actingAs($this->admin)
            ->test(ListHakTriwulans::class)
            ->assertCanSeeTableRecords([$hak])
            ->callTableAction('cabut', $hak);
        $this->assertSame('dicabut', $hak->fresh()->status);

        // objek $hak di memori masih berstatus 'aktif' (yang diubah aksi adalah salinan lain dari database),
        // padahal tombol "Aktifkan kembali" hanya terlihat untuk yang dicabut -> segarkan dulu
        $hak->refresh();

        Livewire::actingAs($this->admin)
            ->test(ListHakTriwulans::class)
            ->callTableAction('aktifkan', $hak);
        $this->assertSame('aktif', $hak->fresh()->status);
    }

    public function test_aksi_beri_hak_dari_panel(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ListHakTriwulans::class)
            ->callTableAction('beri', data: [
                'sekolah_id' => $this->sekolahA->id,
                'tahun_anggaran_id' => $this->taA->id,
                'triwulan' => 3,
                'sumber' => 'gratis',
                'berlaku_sampai' => now()->addMonths(2)->toDateString(),
                'catatan' => 'promo peluncuran',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('hak_triwulan', [
            'sekolah_id' => $this->sekolahA->id, 'tahun_anggaran_id' => $this->taA->id,
            'triwulan' => 3, 'sumber' => 'gratis', 'status' => 'aktif', 'catatan' => 'promo peluncuran',
        ]);
    }

    // ===== Widget dashboard =====

    public function test_widget_statistik_menampilkan_pendapatan_dan_jumlah_sekolah(): void
    {
        $invoice = app(InvoiceService::class)->buatInvoice($this->sekolahA, $this->taA, [1]);
        app(InvoiceService::class)->setujui($invoice, $this->admin);
        $bayar = number_format($invoice->fresh()->total_bayar, 0, ',', '.');

        Livewire::actingAs($this->admin)
            ->test(AdminStatsOverview::class)
            ->assertSuccessful()
            ->assertSee('Total Sekolah')
            ->assertSee('Pendapatan Bulan Ini')
            ->assertSee("Rp {$bayar}")
            ->assertSee('Belum Melengkapi Profil');
    }

    public function test_widget_invoice_menunggu_verifikasi_bisa_setujui_langsung(): void
    {
        $invoice = app(InvoiceService::class)->buatInvoice($this->sekolahA, $this->taA, [1]);
        $invoice->update(['status' => Invoice::MENUNGGU_VERIFIKASI, 'dibayar_at' => now()]);
        $lunas = app(InvoiceService::class)->buatInvoice($this->sekolahB, $this->sekolahB->tahunAnggaran()->first(), [1]);
        $lunas->update(['status' => Invoice::DIBATALKAN]);

        Livewire::actingAs($this->admin)
            ->test(InvoiceMenungguVerifikasiWidget::class)
            ->assertCanSeeTableRecords([$invoice])
            ->assertCanNotSeeTableRecords([$lunas])
            ->callTableAction('setujui', $invoice);

        $this->assertSame(Invoice::LUNAS, $invoice->fresh()->status);
        $this->assertDatabaseHas('hak_triwulan', ['tahun_anggaran_id' => $this->taA->id, 'triwulan' => 1, 'sumber' => 'berbayar']);
    }

    public function test_widget_calon_pembeli_hanya_sekolah_dengan_draft(): void
    {
        $this->isiDataSekolahA();

        Livewire::actingAs($this->admin)
            ->test(CalonPembeliWidget::class)
            ->assertCanSeeTableRecords([$this->sekolahA])
            ->assertCanNotSeeTableRecords([$this->sekolahB]);
    }

    public function test_widget_belum_onboarding_hanya_user_tanpa_sekolah(): void
    {
        Livewire::actingAs($this->admin)
            ->test(BelumOnboardingWidget::class)
            ->assertCanSeeTableRecords([$this->userTanpaSekolah])
            ->assertCanNotSeeTableRecords([$this->userA1, $this->userA2]);
    }

    public function test_widget_grafik_dirender_tanpa_error(): void
    {
        Livewire::actingAs($this->admin)->test(PendaftaranChart::class)->assertSuccessful();
        Livewire::actingAs($this->admin)->test(PendapatanChart::class)->assertSuccessful();
    }

    public function test_dashboard_admin_menampilkan_widget_dan_menu_yang_dikelompokkan(): void
    {
        $this->actingAs($this->admin)->get('/admin')
            ->assertOk()
            ->assertSee('Sekolah &amp; User', false)
            ->assertSee('Keuangan')
            ->assertSee('Rekening Pembayaran')
            ->assertDontSee('Pengaturan Platform');
    }

    public function test_halaman_resource_baru_bisa_dibuka_admin(): void
    {
        $this->actingAs($this->admin);

        $this->get(SekolahResource::getUrl())->assertOk();
        $this->get(UserResource::getUrl())->assertOk();
        $this->get(HakTriwulanResource::getUrl())->assertOk();
    }

    public function test_user_sekolah_tidak_bisa_membuka_panel_admin(): void
    {
        $this->actingAs($this->userA1)->get(SekolahResource::getUrl())->assertForbidden();
    }

    // ===== Ekspor CSV =====

    public function test_ekspor_csv_memakai_bom_header_dan_melindungi_dari_rumus(): void
    {
        $respons = EksporCsv::unduh('tes.csv', ['Nama', 'Catatan'], [
            ['SDN A', 'biasa'],
            ['=HYPERLINK("http://jahat")', '@SUM(A1)'],
            ['+62812', null],
        ]);

        ob_start();
        $respons->sendContent();
        $isi = ob_get_clean();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $isi);
        $this->assertStringContainsString("Nama,Catatan\n", $isi);
        $this->assertStringContainsString("\"SDN A\",biasa\n", $isi);
        // sel berbahaya diberi tanda petik di depan supaya tidak dijalankan sebagai rumus oleh Excel
        $this->assertStringContainsString("\"'=HYPERLINK(\"\"http://jahat\"\")\"", $isi);
        $this->assertStringContainsString("'@SUM(A1)", $isi);
        $this->assertStringContainsString("'+62812", $isi);
        $this->assertStringContainsString('text/csv', $respons->headers->get('Content-Type'));
    }

    public function test_bersihkan_tidak_mengubah_teks_biasa(): void
    {
        $this->assertSame('SDN 3 Rangkasbitung', EksporCsv::bersihkan('SDN 3 Rangkasbitung'));
        $this->assertSame('', EksporCsv::bersihkan(null));
        $this->assertSame('42', EksporCsv::bersihkan(42));
        $this->assertSame("'-5", EksporCsv::bersihkan('-5'));
    }

    public function test_aksi_ekspor_csv_pada_tabel_sekolah_dan_user(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ListSekolahs::class)
            ->callTableAction('ekspor')
            ->assertFileDownloaded();

        Livewire::actingAs($this->admin)
            ->test(ListUsers::class)
            ->callTableAction('ekspor')
            ->assertFileDownloaded();
    }
}
