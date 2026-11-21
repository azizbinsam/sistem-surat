<?php

namespace Tests\Feature;

use App\Exceptions\LanggananException;
use App\Models\Invoice;
use App\Models\MasterBarang;
use App\Models\Pegawai;
use App\Models\Sekolah;
use App\Models\TahunAnggaran;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\AksesTriwulanService;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Volt\Volt;
use Tests\TestCase;

/**
 * Fase 28 — Aturan harga baru: TW1–TW3 2026 gratis (berbasis aturan, bukan baris data), TW4 2026 dan seterusnya berbayar.
 * Di test, "2026" diwakili tahun anggaran sekolah uji supaya hasilnya tidak bergantung pada tanggal hari ini.
 */
class Fase28AturanHargaTest extends TestCase
{
    use RefreshDatabase;

    protected Sekolah $sekolah;
    protected TahunAnggaran $ta;
    protected User $user;
    protected Pegawai $pegawai;
    protected MasterBarang $barang;
    protected int $tahun;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();

        $this->sekolah = Sekolah::create([
            'nama_sekolah' => 'SDN 3 Rangkasbitung Timur', 'kode_sekolah' => 'SDN3RKST', 'npsn' => '20601936',
            'nama_pemerintah' => 'PEMKAB LEBAK', 'nama_dinas' => 'DISDIK', 'alamat' => 'Jl. Contoh', 'tempat' => 'Rangkasbitung',
        ]);
        $this->ta = $this->sekolah->tahunAnggaran()->first();
        $this->tahun = (int) $this->ta->tahun;
        $this->user = User::factory()->create(['sekolah_id' => $this->sekolah->id]);

        $this->barang = MasterBarang::create(['sekolah_id' => $this->sekolah->id, 'kode_barang' => 'ATK-001', 'nama_barang' => 'Kertas HVS', 'satuan_default' => 'Rim']);
        $this->pegawai = Pegawai::create(['sekolah_id' => $this->sekolah->id, 'nama' => 'Budi', 'nip' => '123', 'jabatan' => 'Guru', 'kategori' => 'guru']);

        // pengunci aktif + jendela gratis = TA uji triwulan 1–3 (padanan "TW1–TW3 2026")
        config([
            'langganan.pengunci_aktif' => true,
            'langganan.gratis' => ['tahun' => $this->tahun, 'sampai_triwulan' => 3, 'tampil_di_landing' => true],
        ]);
    }

    protected function akses(): AksesTriwulanService
    {
        return app(AksesTriwulanService::class);
    }

    protected function buatTransaksi(string $ref, string $tanggal): Transaksi
    {
        $t = Transaksi::create([
            'sekolah_id' => $this->sekolah->id, 'nomor_referensi_asal' => $ref, 'tanggal_npb' => $tanggal,
            'pihak_peminta_id' => $this->pegawai->id, 'status' => 'draft',
        ]);
        $t->items()->create(['master_barang_id' => $this->barang->id, 'spesifikasi' => 'S', 'jumlah' => 1, 'satuan' => 'Rim', 'keperluan' => 'x']);

        return $t;
    }

    protected function habiskanKuota(): void
    {
        $this->sekolah->forceFill(['generate_percobaan_terpakai' => 3])->save();
    }

    // ===== Aturan =====

    public function test_tabel_kebenaran_jendela_gratis(): void
    {
        $t = $this->tahun;

        foreach ([1, 2, 3] as $tw) {
            $this->assertTrue($this->akses()->gratisBerdasarkanAturan($t, $tw), "TW{$tw} {$t} harus gratis");
        }
        $this->assertFalse($this->akses()->gratisBerdasarkanAturan($t, 4), 'TW4 harus berbayar');
        $this->assertFalse($this->akses()->gratisBerdasarkanAturan($t + 1, 1), 'tahun berikutnya berbayar');
        $this->assertTrue($this->akses()->gratisBerdasarkanAturan($t - 1, 4), 'sebelum jendela dianggap gratis');
    }

    public function test_tahun_tidak_diketahui_tidak_pernah_gratis(): void
    {
        $this->assertFalse($this->akses()->gratisBerdasarkanAturan(0, 1));
        $this->assertFalse($this->akses()->gratisBerdasarkanAturan(-5, 1));
    }

    public function test_jendela_dimatikan_dengan_tahun_nol(): void
    {
        config(['langganan.gratis.tahun' => 0]);

        $this->assertFalse($this->akses()->gratisBerdasarkanAturan($this->tahun, 1));
    }

    public function test_batas_triwulan_mengikuti_konfigurasi(): void
    {
        config(['langganan.gratis.sampai_triwulan' => 4]);

        $this->assertTrue($this->akses()->gratisBerdasarkanAturan($this->tahun, 4));
        $this->assertFalse($this->akses()->gratisBerdasarkanAturan($this->tahun + 1, 1));
    }

    // ===== Generate =====

    public function test_generate_triwulan_gratis_lolos_walau_kuota_habis_dan_tidak_memakai_kuota(): void
    {
        $this->habiskanKuota();
        $tw1 = $this->buatTransaksi('REF-TW1', "{$this->tahun}-02-10");
        $tw3 = $this->buatTransaksi('REF-TW3', "{$this->tahun}-09-20");

        Volt::actingAs($this->user)->test('pages.transaksi.index')
            ->call('generate', $tw1->id)
            ->assertSet('perluBeli', false)
            ->call('generate', $tw3->id)
            ->assertSet('perluBeli', false);

        $this->assertSame('selesai', $tw1->fresh()->status);
        $this->assertSame('selesai', $tw3->fresh()->status);
        $this->assertSame(3, (int) $this->sekolah->fresh()->generate_percobaan_terpakai);
    }

    public function test_generate_tw4_berbayar_ditolak_kalau_kuota_habis(): void
    {
        $this->habiskanKuota();
        $tw4 = $this->buatTransaksi('REF-TW4', "{$this->tahun}-11-10");

        Volt::actingAs($this->user)->test('pages.transaksi.index')
            ->call('generate', $tw4->id)
            ->assertSet('perluBeli', true)
            ->assertSee('TW4');

        $this->assertSame('draft', $tw4->fresh()->status);
    }

    public function test_tw4_memakai_kuota_percobaan_sedangkan_tw_gratis_tidak(): void
    {
        $tw2 = $this->buatTransaksi('REF-TW2', "{$this->tahun}-05-10"); // gratis
        $tw4 = $this->buatTransaksi('REF-TW4', "{$this->tahun}-11-10"); // kuota

        Volt::actingAs($this->user)->test('pages.transaksi.index')
            ->set('selected', [$tw2->id, $tw4->id])
            ->call('generateBulk')
            ->assertSet('errorMsg', null);

        $this->assertSame('selesai', $tw2->fresh()->status);
        $this->assertSame('selesai', $tw4->fresh()->status);
        $this->assertSame(1, (int) $this->sekolah->fresh()->generate_percobaan_terpakai);
    }

    public function test_gembok_hanya_muncul_di_triwulan_berbayar(): void
    {
        $this->habiskanKuota();
        $this->buatTransaksi('REF-TW1', "{$this->tahun}-02-10");
        $this->buatTransaksi('REF-TW4', "{$this->tahun}-11-10");

        Volt::actingAs($this->user)->test('pages.transaksi.index')
            ->assertSee('🔒 beli TW4')
            ->assertDontSee('🔒 beli TW1');
    }

    // ===== Invoice =====

    public function test_tidak_bisa_membeli_triwulan_gratis(): void
    {
        try {
            app(InvoiceService::class)->buatInvoice($this->sekolah, $this->ta, [1]);
            $this->fail('Seharusnya menolak pembelian triwulan gratis');
        } catch (LanggananException $e) {
            $this->assertStringContainsString('TW1', $e->getMessage());
            $this->assertStringContainsString('gratis, tidak perlu dibeli', $e->getMessage());
        }

        $this->expectException(LanggananException::class);
        $this->expectExceptionMessage('TW3');
        app(InvoiceService::class)->buatInvoice($this->sekolah, $this->ta, [3, 4]); // TW3 gratis menggagalkan seluruh pesanan
    }

    public function test_tw4_bisa_dibeli_dengan_harga_satu_triwulan(): void
    {
        $invoice = app(InvoiceService::class)->buatInvoice($this->sekolah, $this->ta, [4]);

        $this->assertSame(39000, $invoice->total);
        $this->assertSame(Invoice::MENUNGGU_PEMBAYARAN, $invoice->status);
    }

    // ===== Halaman Langganan =====

    public function test_kartu_langganan_menandai_tw1_sampai_tw3_gratis_dan_hanya_tw4_bisa_dipilih(): void
    {
        $komponen = Volt::actingAs($this->user)->test('pages.langganan.index')
            ->assertSee('Gratis — tidak perlu dibeli')
            ->call('pilihSemuaSisa');

        $komponen->assertSet('dipilih', [4]);
    }

    public function test_buat_invoice_tw4_dari_halaman_langganan(): void
    {
        Volt::actingAs($this->user)->test('pages.langganan.index')
            ->set('dipilih', [4])
            ->call('buatInvoice')
            ->assertSet('errorMsg', null);

        $invoice = Invoice::where('sekolah_id', $this->sekolah->id)->first();
        $this->assertNotNull($invoice);
        $this->assertSame(39000, $invoice->total);
        $this->assertSame(4, $invoice->items->first()->triwulan);
    }

    public function test_memilih_triwulan_gratis_lewat_manipulasi_tetap_ditolak(): void
    {
        Volt::actingAs($this->user)->test('pages.langganan.index')
            ->set('dipilih', [1, 2])
            ->call('buatInvoice');

        $this->assertSame(0, Invoice::count());
    }

    // ===== Pengingat =====

    public function test_pengingat_draft_tidak_dikirim_untuk_triwulan_gratis(): void
    {
        $this->buatTransaksi('REF-TW1', "{$this->tahun}-02-10");

        $this->artisan('langganan:kirim-pengingat', ['--tanggal' => "{$this->tahun}-04-01"])->assertSuccessful();

        Mail::assertNothingSent();
    }

    public function test_pengingat_draft_dikirim_untuk_tw4_berbayar(): void
    {
        $this->buatTransaksi('REF-TW4', "{$this->tahun}-11-10");

        $this->artisan('langganan:kirim-pengingat', ['--tanggal' => ($this->tahun + 1) . '-01-01'])->assertSuccessful();

        Mail::assertSent(\App\Mail\NotifikasiLanggananMail::class, fn($m) => $m->tampilan === 'draft-belum-digenerate'
            && $m->data['namaTriwulan'] === "TW4 {$this->tahun}");
    }
}
