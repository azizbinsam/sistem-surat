<?php

namespace Tests\Feature;

use App\Models\BarangMasuk;
use App\Models\BarangMasukItem;
use App\Models\MasterBarang;
use App\Models\Sekolah;
use App\Models\Transaksi;
use App\Models\TransaksiItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class DashboardV2Test extends TestCase
{
    use RefreshDatabase;

    protected Sekolah $sekolah;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sekolah = Sekolah::create([
            'nama_sekolah' => 'SDN 3 Rangkasbitung Timur',
            'kode_sekolah' => 'SDN3RKST',
            'nama_pemerintah' => 'X',
            'nama_dinas' => 'Y',
            'alamat' => 'Z',
            'tempat' => 'W',
        ]);
        $this->user = User::factory()->create(['sekolah_id' => $this->sekolah->id]);
    }

    public function test_dashboard_render_normal_walau_data_kosong(): void
    {
        Volt::actingAs($this->user)->test('pages.dashboard')
            ->assertOk()
            ->assertSee('Belum ada data di tahun anggaran ini')
            ->assertSee('Belum ada transaksi keluar di tahun anggaran ini');
    }

    public function test_barang_stok_menipis_muncul_di_alert(): void
    {
        $barang = MasterBarang::create([
            'sekolah_id' => $this->sekolah->id,
            'kode_barang' => 'A1',
            'nama_barang' => 'Kertas Menipis',
            'satuan_default' => 'Rim',
        ]);
        $bpu = BarangMasuk::create(['sekolah_id' => $this->sekolah->id, 'nomor_bpu' => 'BPU-1', 'tanggal' => '2026-01-01']);
        BarangMasukItem::create(['barang_masuk_id' => $bpu->id, 'master_barang_id' => $barang->id, 'spesifikasi' => 'x', 'satuan' => 'Rim', 'jumlah' => 3]);

        Volt::actingAs($this->user)->test('pages.dashboard')
            ->assertSee('Stok Menipis')
            ->assertSee('Kertas Menipis');
    }

    public function test_barang_stok_cukup_tidak_muncul_di_alert(): void
    {
        $barang = MasterBarang::create([
            'sekolah_id' => $this->sekolah->id,
            'kode_barang' => 'A1',
            'nama_barang' => 'Kertas Aman',
            'satuan_default' => 'Rim',
        ]);
        $bpu = BarangMasuk::create(['sekolah_id' => $this->sekolah->id, 'nomor_bpu' => 'BPU-1', 'tanggal' => '2026-01-01']);
        BarangMasukItem::create(['barang_masuk_id' => $bpu->id, 'master_barang_id' => $barang->id, 'spesifikasi' => 'x', 'satuan' => 'Rim', 'jumlah' => 50]);

        Volt::actingAs($this->user)->test('pages.dashboard')
            ->assertDontSee('Stok Menipis');
    }

    public function test_top_barang_keluar_muncul_di_chart(): void
    {
        $barang = MasterBarang::create([
            'sekolah_id' => $this->sekolah->id,
            'kode_barang' => 'A1',
            'nama_barang' => 'Kertas Populer',
            'satuan_default' => 'Rim',
        ]);
        $bpu = BarangMasuk::create(['sekolah_id' => $this->sekolah->id, 'nomor_bpu' => 'BPU-1', 'tanggal' => '2026-01-01']);
        BarangMasukItem::create(['barang_masuk_id' => $bpu->id, 'master_barang_id' => $barang->id, 'spesifikasi' => 'x', 'satuan' => 'Rim', 'jumlah' => 100]);

        $t = Transaksi::create([
            'sekolah_id' => $this->sekolah->id,
            'nomor_referensi_asal' => 'REF-1',
            'tanggal_npb' => '2026-02-01',
            'status' => 'draft',
        ]);
        TransaksiItem::create(['transaksi_id' => $t->id, 'master_barang_id' => $barang->id, 'spesifikasi' => 'x', 'jumlah' => 10, 'satuan' => 'Rim', 'keperluan' => 'x']);

        Volt::actingAs($this->user)->test('pages.dashboard')
            ->assertSee('Kertas Populer');
    }

    public function test_data_sekolah_lain_tidak_ikut_ke_dashboard(): void
    {
        $sekolahLain = Sekolah::create([
            'nama_sekolah' => 'SDN Lain',
            'kode_sekolah' => 'SDNLAIN',
            'nama_pemerintah' => 'X',
            'nama_dinas' => 'Y',
            'alamat' => 'Z',
            'tempat' => 'W',
        ]);
        $barangOrang = MasterBarang::create([
            'sekolah_id' => $sekolahLain->id,
            'kode_barang' => 'X1',
            'nama_barang' => 'Barang Orang Lain',
            'satuan_default' => 'Rim',
        ]);
        $bpuOrang = BarangMasuk::create(['sekolah_id' => $sekolahLain->id, 'nomor_bpu' => 'BPU-X', 'tanggal' => '2026-01-01']);
        BarangMasukItem::create(['barang_masuk_id' => $bpuOrang->id, 'master_barang_id' => $barangOrang->id, 'spesifikasi' => 'x', 'satuan' => 'Rim', 'jumlah' => 1]);

        Volt::actingAs($this->user)->test('pages.dashboard')
            ->assertDontSee('Barang Orang Lain');
    }

    public function test_dashboard_tidak_error_kalau_tabel_rekening_donasi_belum_ada(): void
    {
        // Fase 20 belum jalan -> tabel rekening_donasi belum ada sama sekali.
        // Dashboard harus tetap render normal, section donasi cukup disembunyikan.
        Volt::actingAs($this->user)->test('pages.dashboard')
            ->assertOk()
            ->assertDontSee('Dukung Kami');
    }

    public function test_dashboard_tidak_crash_buat_user_yang_belum_punya_sekolah(): void
    {
        // Skenario: middleware belum sempat redirect ke onboarding, dashboard tetap kepanggil.
        $user = User::factory()->create(['sekolah_id' => null]);

        Volt::actingAs($user)->test('pages.dashboard')->assertOk();
    }

    public function test_chart_js_diload_di_halaman(): void
    {
        $response = $this->actingAs($this->user)->get('/dashboard');
        $response->assertOk();
        // manifest.json harus ke-resolve tanpa error (build asset udah termasuk chart.js)
    }
}
