<?php

namespace Tests\Feature;

use App\Models\BarangMasuk;
use App\Models\MasterBarang;
use App\Models\Pegawai;
use App\Models\Sekolah;
use App\Models\TahunAnggaran;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

/**
 * Fase 23 — Fondasi & revisi cepat:
 *  - Generate dipisah dari Unduh Word (generate cuma bikin nomor, tanpa file)
 *  - Bulk generate dibatalkan total kalau ada pihak peminta yang belum dipilih
 *  - Filter tanggal & triwulan di Penerimaan Barang & Pengeluaran Barang
 *  - Pilihan jumlah baris di Persediaan
 */
class Fase23FondasiTest extends TestCase
{
    use RefreshDatabase;

    protected Sekolah $sekolah;
    protected TahunAnggaran $tahunAnggaran;
    protected User $user;
    protected MasterBarang $barang;
    protected Pegawai $pegawai;
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

        $this->tahunAnggaran = $this->sekolah->tahunAnggaran()->first();
        $this->tahun = (int) $this->tahunAnggaran->tahun;

        $this->user = User::factory()->create(['sekolah_id' => $this->sekolah->id]);
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

    protected function buatTransaksi(string $ref, string $tanggal, bool $denganPeminta = true): Transaksi
    {
        $t = Transaksi::create([
            'sekolah_id' => $this->sekolah->id,
            'nomor_referensi_asal' => $ref,
            'tanggal_npb' => $tanggal,
            'pihak_peminta_id' => $denganPeminta ? $this->pegawai->id : null,
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

    // ===== Generate vs Unduh =====

    public function test_generate_satuan_hanya_membuat_nomor_dan_mengubah_status(): void
    {
        $t = $this->buatTransaksi('REF-1', "{$this->tahun}-01-10");

        Volt::actingAs($this->user)->test('pages.transaksi.index')
            ->call('generate', $t->id)
            ->assertSet('errorMsg', null);

        $t->refresh();
        $this->assertNotNull($t->nomor_npb);
        $this->assertNotNull($t->nomor_spb);
        $this->assertNotNull($t->nomor_sppb);
        $this->assertSame('selesai', $t->status);
    }

    public function test_generate_transaksi_yang_sudah_selesai_tidak_membuat_nomor_baru(): void
    {
        $t = $this->buatTransaksi('REF-1', "{$this->tahun}-01-10");
        $komponen = Volt::actingAs($this->user)->test('pages.transaksi.index');

        $komponen->call('generate', $t->id);
        $nomorPertama = $t->fresh()->nomor_npb;
        $counter = $this->tahunAnggaran->fresh()->nomor_urut_terakhir;

        $komponen->call('generate', $t->id)
            ->assertSet('errorMsg', 'Transaksi ini sudah di-generate.');

        $this->assertSame($nomorPertama, $t->fresh()->nomor_npb);
        $this->assertSame($counter, $this->tahunAnggaran->fresh()->nomor_urut_terakhir);
    }

    public function test_generate_tanpa_pihak_peminta_ditolak(): void
    {
        $t = $this->buatTransaksi('REF-1', "{$this->tahun}-01-10", false);

        Volt::actingAs($this->user)->test('pages.transaksi.index')
            ->call('generate', $t->id)
            ->assertSet('errorMsg', 'Pihak yang meminta belum dipilih. Lengkapi dulu sebelum generate surat.');

        $this->assertNull($t->fresh()->nomor_npb);
        $this->assertSame('draft', $t->fresh()->status);
    }

    public function test_unduh_word_untuk_transaksi_draft_ditolak(): void
    {
        $t = $this->buatTransaksi('REF-1', "{$this->tahun}-01-10");

        Volt::actingAs($this->user)->test('pages.transaksi.index')
            ->call('unduhWord', $t->id)
            ->assertSet('errorMsg', 'Surat ini belum di-generate. Klik Generate dulu sebelum mengunduh.');
    }

    public function test_bulk_generate_dibatalkan_total_kalau_ada_yang_belum_punya_pihak_peminta(): void
    {
        $a = $this->buatTransaksi('REF-A', "{$this->tahun}-01-10");
        $b = $this->buatTransaksi('REF-B', "{$this->tahun}-01-11", false);
        $counterSebelum = $this->tahunAnggaran->fresh()->nomor_urut_terakhir;

        Volt::actingAs($this->user)->test('pages.transaksi.index')
            ->set('selected', [$a->id, $b->id])
            ->call('generateBulk');

        // tidak ada satu pun yang digenerate, counter tidak bergerak
        $this->assertNull($a->fresh()->nomor_npb);
        $this->assertNull($b->fresh()->nomor_npb);
        $this->assertSame('draft', $a->fresh()->status);
        $this->assertSame($counterSebelum, $this->tahunAnggaran->fresh()->nomor_urut_terakhir);
    }

    public function test_bulk_generate_tanpa_pilihan_menampilkan_error(): void
    {
        Volt::actingAs($this->user)->test('pages.transaksi.index')
            ->call('generateBulk')
            ->assertSet('errorMsg', 'Pilih minimal 1 transaksi untuk generate.');
    }

    public function test_unduh_bulk_tanpa_transaksi_yang_sudah_digenerate_menampilkan_error(): void
    {
        $t = $this->buatTransaksi('REF-1', "{$this->tahun}-01-10");

        Volt::actingAs($this->user)->test('pages.transaksi.index')
            ->set('selected', [$t->id])
            ->call('unduhWordBulk')
            ->assertSet('errorMsg', 'Belum ada transaksi terpilih yang sudah di-generate. Generate dulu sebelum mengunduh.');
    }

    public function test_menu_aksi_menampilkan_tombol_sesuai_status(): void
    {
        $draft = $this->buatTransaksi('REF-DRAFT', "{$this->tahun}-01-10");
        $selesai = $this->buatTransaksi('REF-SELESAI', "{$this->tahun}-01-11");

        $komponen = Volt::actingAs($this->user)->test('pages.transaksi.index');
        $komponen->call('generate', $selesai->id);

        $komponen->assertSeeHtml('wire:click="generate(' . $draft->id . ')"')
            ->assertDontSeeHtml('wire:click="unduhWord(' . $draft->id . ')"')
            ->assertSeeHtml('wire:click="unduhWord(' . $selesai->id . ')"')
            ->assertDontSeeHtml('wire:click="generate(' . $selesai->id . ')"')
            ->assertSeeHtml('wire:click="mintaBatalkanNomor(' . $selesai->id . ')"')
            ->assertDontSee('Generate PDF');
    }

    // ===== Filter periode: Pengeluaran Barang =====

    public function test_pilih_triwulan_mengisi_tanggal_dan_memfilter_pengeluaran(): void
    {
        $this->buatTransaksi('REF-TW1', "{$this->tahun}-02-10");
        $this->buatTransaksi('REF-TW3', "{$this->tahun}-08-10");

        Volt::actingAs($this->user)->test('pages.transaksi.index')
            ->set('triwulan', '1')
            ->assertSet('tanggalAwal', "{$this->tahun}-01-01")
            ->assertSet('tanggalAkhir', "{$this->tahun}-03-31")
            ->assertSee('REF-TW1')
            ->assertDontSee('REF-TW3');
    }

    public function test_ubah_tanggal_manual_mengosongkan_pilihan_triwulan(): void
    {
        Volt::actingAs($this->user)->test('pages.transaksi.index')
            ->set('triwulan', '2')
            ->set('tanggalAwal', "{$this->tahun}-04-05")
            ->assertSet('triwulan', '');
    }

    public function test_rentang_tanggal_manual_memfilter_pengeluaran(): void
    {
        $this->buatTransaksi('REF-AWAL', "{$this->tahun}-01-05");
        $this->buatTransaksi('REF-TENGAH', "{$this->tahun}-05-15");

        Volt::actingAs($this->user)->test('pages.transaksi.index')
            ->set('tanggalAwal', "{$this->tahun}-05-01")
            ->set('tanggalAkhir', "{$this->tahun}-05-31")
            ->assertSee('REF-TENGAH')
            ->assertDontSee('REF-AWAL');
    }

    public function test_reset_filter_periode_mengosongkan_semuanya(): void
    {
        Volt::actingAs($this->user)->test('pages.transaksi.index')
            ->set('triwulan', '4')
            ->call('resetFilterPeriode')
            ->assertSet('triwulan', '')
            ->assertSet('tanggalAwal', '')
            ->assertSet('tanggalAkhir', '');
    }

    public function test_tanggal_filter_asal_asalan_diabaikan_tanpa_error(): void
    {
        $this->buatTransaksi('REF-1', "{$this->tahun}-01-10");

        Volt::actingAs($this->user)->test('pages.transaksi.index')
            ->set('tanggalAwal', 'bukan-tanggal')
            ->assertSee('REF-1');
    }

    public function test_pencarian_nomor_surat_tidak_membocorkan_transaksi_sekolah_lain(): void
    {
        $sekolahLain = Sekolah::create([
            'nama_sekolah' => 'SDN Lain',
            'kode_sekolah' => 'SDNLAIN',
            'npsn' => '20600001',
            'nama_pemerintah' => 'X',
            'nama_dinas' => 'Y',
            'alamat' => 'Z',
            'tempat' => 'W',
        ]);
        Transaksi::create([
            'sekolah_id' => $sekolahLain->id,
            'nomor_referensi_asal' => 'REF-SEKOLAH-LAIN',
            'nomor_npb' => 'NPB-BOCOR-001',
            'tanggal_npb' => "{$this->tahun}-01-10",
            'status' => 'selesai',
        ]);

        Volt::actingAs($this->user)->test('pages.transaksi.index')
            ->set('search', 'NPB-BOCOR')
            ->assertDontSee('REF-SEKOLAH-LAIN');
    }

    // ===== Filter periode: Penerimaan Barang =====

    public function test_pilih_triwulan_memfilter_penerimaan_barang(): void
    {
        BarangMasuk::create(['sekolah_id' => $this->sekolah->id, 'nomor_bpu' => 'BPU-TW1', 'tanggal' => "{$this->tahun}-02-01"]);
        BarangMasuk::create(['sekolah_id' => $this->sekolah->id, 'nomor_bpu' => 'BPU-TW4', 'tanggal' => "{$this->tahun}-11-01"]);

        Volt::actingAs($this->user)->test('pages.barang-masuk.index')
            ->set('triwulan', '4')
            ->assertSet('tanggalAwal', "{$this->tahun}-10-01")
            ->assertSee('BPU-TW4')
            ->assertDontSee('BPU-TW1');
    }

    // ===== Persediaan: jumlah baris =====

    public function test_persediaan_bisa_ganti_jumlah_baris_dan_nilai_aneh_kembali_ke_default(): void
    {
        for ($i = 1; $i <= 30; $i++) {
            MasterBarang::create(['sekolah_id' => $this->sekolah->id, 'kode_barang' => "B{$i}", 'nama_barang' => "Barang {$i}", 'satuan_default' => 'Pak']);
        }

        Volt::actingAs($this->user)->test('pages.persediaan.index')
            ->assertViewHas('daftarBarang', fn($p) => $p->perPage() === 15)
            ->set('perPage', '25')
            ->assertViewHas('daftarBarang', fn($p) => $p->perPage() === 25)
            ->set('perPage', '99999')
            ->assertViewHas('daftarBarang', fn($p) => $p->perPage() === 15);
    }

    // ===== Label =====

    public function test_label_menu_dan_judul_berubah_menjadi_pengeluaran_barang(): void
    {
        Volt::actingAs($this->user)->test('pages.transaksi.index')
            ->assertSee('Pengeluaran Barang')
            ->assertDontSee('Transaksi Keluar');
    }
}
