<?php

namespace Tests\Feature;

use App\Models\BarangMasuk;
use App\Models\MasterBarang;
use App\Models\Pegawai;
use App\Models\Sekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class RapikanTampilanIndexTest extends TestCase
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

    // ===== Master Barang =====

    public function test_master_barang_bisa_di_sort_per_kolom(): void
    {
        MasterBarang::create(['sekolah_id' => $this->sekolah->id, 'kode_barang' => 'B', 'nama_barang' => 'Buku', 'satuan_default' => 'Pak']);
        MasterBarang::create(['sekolah_id' => $this->sekolah->id, 'kode_barang' => 'A', 'nama_barang' => 'Amplop', 'satuan_default' => 'Pak']);

        $component = Volt::actingAs($this->user)->test('pages.master-barang.index');

        // default asc by nama_barang -> Amplop duluan
        $component->assertSeeInOrder(['Amplop', 'Buku']);

        // klik sort nama_barang lagi -> jadi desc
        $component->call('sortir', 'nama_barang')->assertSeeInOrder(['Buku', 'Amplop']);
    }

    public function test_master_barang_bisa_difilter_kategori(): void
    {
        MasterBarang::create(['sekolah_id' => $this->sekolah->id, 'kode_barang' => 'A', 'nama_barang' => 'ATK A', 'kategori' => 'ATK', 'satuan_default' => 'Pak']);
        MasterBarang::create(['sekolah_id' => $this->sekolah->id, 'kode_barang' => 'B', 'nama_barang' => 'Elektronik B', 'kategori' => 'Elektronik', 'satuan_default' => 'Unit']);

        Volt::actingAs($this->user)->test('pages.master-barang.index')
            ->set('filterKategori', 'ATK')
            ->assertSee('ATK A')
            ->assertDontSee('Elektronik B');
    }

    // ===== Barang Masuk =====

    public function test_barang_masuk_bisa_dicari_by_nomor_bpu(): void
    {
        BarangMasuk::create(['sekolah_id' => $this->sekolah->id, 'nomor_bpu' => 'BPU-001', 'tanggal' => '2026-01-01']);
        BarangMasuk::create(['sekolah_id' => $this->sekolah->id, 'nomor_bpu' => 'BPU-002', 'tanggal' => '2026-01-02']);

        Volt::actingAs($this->user)->test('pages.barang-masuk.index')
            ->set('search', 'BPU-001')
            ->assertSee('BPU-001')
            ->assertDontSee('BPU-002');
    }

    public function test_barang_masuk_bisa_di_sort_per_kolom(): void
    {
        BarangMasuk::create(['sekolah_id' => $this->sekolah->id, 'nomor_bpu' => 'BPU-AAA', 'tanggal' => '2026-01-01']);
        BarangMasuk::create(['sekolah_id' => $this->sekolah->id, 'nomor_bpu' => 'BPU-ZZZ', 'tanggal' => '2026-01-02']);

        Volt::actingAs($this->user)->test('pages.barang-masuk.index')
            ->call('sortir', 'nomor_bpu')
            ->assertSeeInOrder(['BPU-AAA', 'BPU-ZZZ']);
    }

    // ===== Pegawai =====

    public function test_pegawai_bisa_difilter_kategori(): void
    {
        Pegawai::create(['sekolah_id' => $this->sekolah->id, 'nama' => 'Budi', 'jabatan' => 'Guru Kelas', 'kategori' => 'guru']);
        Pegawai::create(['sekolah_id' => $this->sekolah->id, 'nama' => 'Siti', 'jabatan' => 'Kepala Sekolah', 'kategori' => 'kepala_sekolah']);

        Volt::actingAs($this->user)->test('pages.pegawai.index')
            ->set('filterKategori', 'guru')
            ->assertSee('Budi')
            ->assertDontSee('Siti');
    }

    public function test_pegawai_bisa_di_sort_per_kolom(): void
    {
        Pegawai::create(['sekolah_id' => $this->sekolah->id, 'nama' => 'Zainal', 'jabatan' => 'Guru', 'kategori' => 'guru']);
        Pegawai::create(['sekolah_id' => $this->sekolah->id, 'nama' => 'Ani', 'jabatan' => 'Guru', 'kategori' => 'guru']);

        $component = Volt::actingAs($this->user)->test('pages.pegawai.index');

        $component->assertSeeInOrder(['Ani', 'Zainal']); // default asc

        $component->call('sortir', 'nama')->assertSeeInOrder(['Zainal', 'Ani']); // toggle desc
    }

    // ===== Sort injection protection (whitelist kolom) =====

    public function test_sortir_menolak_kolom_yang_tidak_di_whitelist(): void
    {
        MasterBarang::create(['sekolah_id' => $this->sekolah->id, 'kode_barang' => 'A', 'nama_barang' => 'Amplop', 'satuan_default' => 'Pak']);

        // Kolom sensitif kayak 'id' atau nama kolom sembarangan nggak ada di whitelist -> diabaikan, nggak error
        Volt::actingAs($this->user)->test('pages.master-barang.index')
            ->call('sortir', 'sekolah_id')
            ->assertOk()
            ->assertSet('sortBy', 'nama_barang'); // nggak berubah
    }

    // ===== Pagination custom view =====

    public function test_pagination_pakai_view_custom_bukan_default_laravel(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            MasterBarang::create(['sekolah_id' => $this->sekolah->id, 'kode_barang' => "A{$i}", 'nama_barang' => "Barang {$i}", 'satuan_default' => 'Pak']);
        }

        $html = Volt::actingAs($this->user)->test('pages.master-barang.index')->html();

        // "rounded-l-md" cuma ada di view tailwind.blade.php bawaan Laravel, nggak dipakai
        // di view custom kita -- kalau ini nggak muncul, berarti override-nya beneran jalan.
        $this->assertStringNotContainsString('rounded-l-md', $html);
        $this->assertStringContainsString('Menampilkan', $html); // teks dari view custom kita
    }

    // ===== Per-page selector (10/20/50/Semua) — dibutuhkan biar bulk select/generate
    // bisa nyakup semua data, nggak kebatas per halaman default =====

    public function test_master_barang_default_10_per_halaman(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            MasterBarang::create(['sekolah_id' => $this->sekolah->id, 'kode_barang' => "A{$i}", 'nama_barang' => "Barang {$i}", 'satuan_default' => 'Pak']);
        }

        Volt::actingAs($this->user)->test('pages.master-barang.index')
            ->assertViewHas('daftarBarang', fn($p) => $p->count() === 10);
    }

    public function test_master_barang_perpage_semua_nampilin_semua_baris(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            MasterBarang::create(['sekolah_id' => $this->sekolah->id, 'kode_barang' => "A{$i}", 'nama_barang' => "Barang {$i}", 'satuan_default' => 'Pak']);
        }

        Volt::actingAs($this->user)->test('pages.master-barang.index')
            ->set('perPage', 'semua')
            ->assertViewHas('daftarBarang', fn($p) => $p->count() === 15);
    }

    public function test_pegawai_perpage_semua_nampilin_semua_baris(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            Pegawai::create(['sekolah_id' => $this->sekolah->id, 'nama' => "Pegawai {$i}", 'jabatan' => 'Guru', 'kategori' => 'guru']);
        }

        Volt::actingAs($this->user)->test('pages.pegawai.index')
            ->set('perPage', 'semua')
            ->assertViewHas('daftarPegawai', fn($p) => $p->count() === 12);
    }

    public function test_barang_masuk_perpage_semua_nampilin_semua_baris(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            BarangMasuk::create(['sekolah_id' => $this->sekolah->id, 'nomor_bpu' => "BPU-{$i}", 'tanggal' => '2026-01-01']);
        }

        Volt::actingAs($this->user)->test('pages.barang-masuk.index')
            ->set('perPage', 'semua')
            ->assertViewHas('daftarBpu', fn($p) => $p->count() === 12);
    }

    public function test_transaksi_perpage_semua_nampilin_semua_baris(): void
    {
        $pegawai = Pegawai::create(['sekolah_id' => $this->sekolah->id, 'nama' => 'Budi', 'jabatan' => 'Guru', 'kategori' => 'guru']);
        for ($i = 1; $i <= 12; $i++) {
            \App\Models\Transaksi::create([
                'sekolah_id' => $this->sekolah->id,
                'nomor_referensi_asal' => "REF-{$i}",
                'tanggal_npb' => '2026-01-01',
                'pihak_peminta_id' => $pegawai->id,
                'status' => 'draft',
            ]);
        }

        Volt::actingAs($this->user)->test('pages.transaksi.index')
            ->set('perPage', 'semua')
            ->assertViewHas('daftarTransaksi', fn($p) => $p->count() === 12);
    }

    public function test_ganti_perpage_reset_ke_halaman_1(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            MasterBarang::create(['sekolah_id' => $this->sekolah->id, 'kode_barang' => "A{$i}", 'nama_barang' => "Barang {$i}", 'satuan_default' => 'Pak']);
        }

        Volt::actingAs($this->user)->test('pages.master-barang.index')
            ->call('gotoPage', 2)
            ->set('perPage', '20')
            ->assertViewHas('daftarBarang', fn($p) => $p->currentPage() === 1);
    }

    // ===== Proteksi: barang yang udah dipakai di penerimaan/transaksi keluar
    // nggak boleh dihapus, karena bakal bikin relasi jadi null (crash pas
    // generate surat, dashboard, dll) =====

    public function test_barang_yang_sudah_dipakai_di_penerimaan_tidak_bisa_dihapus_satuan(): void
    {
        $barang = MasterBarang::create(['sekolah_id' => $this->sekolah->id, 'kode_barang' => 'A1', 'nama_barang' => 'Kertas HVS', 'satuan_default' => 'Rim']);
        $bpu = BarangMasuk::create(['sekolah_id' => $this->sekolah->id, 'nomor_bpu' => 'BPU-1', 'tanggal' => '2026-01-01']);
        $bpu->items()->create(['master_barang_id' => $barang->id, 'spesifikasi' => 'Standar', 'satuan' => 'Rim', 'jumlah' => 5]);

        Volt::actingAs($this->user)->test('pages.master-barang.index')
            ->call('mintaHapusSatuan', $barang->id)
            ->assertSet('modalHapusTampil', false); // modal nggak jadi kebuka

        $this->assertNotSoftDeleted($barang);
    }

    public function test_barang_yang_sudah_dipakai_di_transaksi_keluar_tidak_bisa_dihapus_satuan(): void
    {
        $barang = MasterBarang::create(['sekolah_id' => $this->sekolah->id, 'kode_barang' => 'A1', 'nama_barang' => 'Kertas HVS', 'satuan_default' => 'Rim']);
        $pegawai = Pegawai::create(['sekolah_id' => $this->sekolah->id, 'nama' => 'Budi', 'jabatan' => 'Guru', 'kategori' => 'guru']);
        $transaksi = \App\Models\Transaksi::create(['sekolah_id' => $this->sekolah->id, 'nomor_referensi_asal' => 'REF-1', 'tanggal_npb' => '2026-01-01', 'pihak_peminta_id' => $pegawai->id, 'status' => 'draft']);
        $transaksi->items()->create(['master_barang_id' => $barang->id, 'spesifikasi' => 'Standar', 'satuan' => 'Rim', 'jumlah' => 1, 'keperluan' => 'x']);

        Volt::actingAs($this->user)->test('pages.master-barang.index')
            ->call('mintaHapusSatuan', $barang->id)
            ->assertSet('modalHapusTampil', false);

        $this->assertNotSoftDeleted($barang);
    }

    public function test_barang_yang_belum_pernah_dipakai_tetap_bisa_dihapus(): void
    {
        $barang = MasterBarang::create(['sekolah_id' => $this->sekolah->id, 'kode_barang' => 'A1', 'nama_barang' => 'Kertas HVS', 'satuan_default' => 'Rim']);

        Volt::actingAs($this->user)->test('pages.master-barang.index')
            ->call('mintaHapusSatuan', $barang->id)
            ->assertSet('modalHapusTampil', true)
            ->call('eksekusiHapus');

        $this->assertSoftDeleted($barang);
    }

    public function test_hapus_bulk_skip_yang_sudah_dipakai_tapi_tetap_hapus_yang_belum(): void
    {
        $barangTerpakai = MasterBarang::create(['sekolah_id' => $this->sekolah->id, 'kode_barang' => 'A1', 'nama_barang' => 'Kertas HVS', 'satuan_default' => 'Rim']);
        $barangAman = MasterBarang::create(['sekolah_id' => $this->sekolah->id, 'kode_barang' => 'A2', 'nama_barang' => 'Spidol', 'satuan_default' => 'Buah']);

        $bpu = BarangMasuk::create(['sekolah_id' => $this->sekolah->id, 'nomor_bpu' => 'BPU-1', 'tanggal' => '2026-01-01']);
        $bpu->items()->create(['master_barang_id' => $barangTerpakai->id, 'spesifikasi' => 'Standar', 'satuan' => 'Rim', 'jumlah' => 5]);

        Volt::actingAs($this->user)->test('pages.master-barang.index')
            ->set('selected', [$barangTerpakai->id, $barangAman->id])
            ->call('mintaHapusBulk')
            ->assertSet('idBisaDihapusBulk', [$barangAman->id])
            ->assertSet('namaTidakBisaDihapusBulk', ['Kertas HVS'])
            ->call('eksekusiHapus');

        $this->assertNotSoftDeleted($barangTerpakai);
        $this->assertSoftDeleted($barangAman);
    }

    public function test_hapus_bulk_semua_terpilih_udah_dipakai_tidak_ada_yang_kehapus(): void
    {
        $barang = MasterBarang::create(['sekolah_id' => $this->sekolah->id, 'kode_barang' => 'A1', 'nama_barang' => 'Kertas HVS', 'satuan_default' => 'Rim']);
        $bpu = BarangMasuk::create(['sekolah_id' => $this->sekolah->id, 'nomor_bpu' => 'BPU-1', 'tanggal' => '2026-01-01']);
        $bpu->items()->create(['master_barang_id' => $barang->id, 'spesifikasi' => 'Standar', 'satuan' => 'Rim', 'jumlah' => 5]);

        Volt::actingAs($this->user)->test('pages.master-barang.index')
            ->set('selected', [$barang->id])
            ->call('mintaHapusBulk')
            ->assertSet('modalHapusTampil', false);

        $this->assertNotSoftDeleted($barang);
    }
}
