<?php

namespace Tests\Feature;

use App\Models\MasterBarang;
use App\Models\Pegawai;
use App\Models\Sekolah;
use App\Models\Transaksi;
use App\Models\TransaksiItem;
use App\Models\User;
use App\Services\PersediaanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class TransaksiSederhanakanUiTest extends TestCase
{
    use RefreshDatabase;

    protected Sekolah $sekolah;
    protected User $user;
    protected MasterBarang $barang;
    protected Pegawai $pegawai;

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

    protected function buatTransaksi(string $status, string $ref, ?string $nomorNpb = null): Transaksi
    {
        $t = Transaksi::create([
            'sekolah_id' => $this->sekolah->id,
            'nomor_referensi_asal' => $ref,
            'nomor_npb' => $nomorNpb,
            'tanggal_npb' => '2026-02-01',
            'pihak_peminta_id' => $this->pegawai->id,
            'status' => $status,
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

    public function test_draft_dan_selesai_tampil_bareng_tanpa_tab(): void
    {
        $this->buatTransaksi('draft', 'REF-DRAFT');
        $this->buatTransaksi('selesai', 'REF-SELESAI', 'NPB-001');

        Volt::actingAs($this->user)->test('pages.transaksi.index')
            ->assertSee('REF-DRAFT')
            ->assertSee('REF-SELESAI')
            ->assertDontSee('wire:click="$set(\'tab\''); // sistem tab udah nggak ada
    }

    public function test_bisa_filter_by_status(): void
    {
        $this->buatTransaksi('draft', 'REF-DRAFT');
        $this->buatTransaksi('selesai', 'REF-SELESAI', 'NPB-001');

        Volt::actingAs($this->user)->test('pages.transaksi.index')
            ->set('filterStatus', 'draft')
            ->assertSee('REF-DRAFT')
            ->assertDontSee('REF-SELESAI');
    }

    public function test_bisa_cari_by_nomor_referensi_atau_nomor_surat(): void
    {
        $this->buatTransaksi('selesai', 'REF-001', 'NPB-XYZ-001');
        $this->buatTransaksi('selesai', 'REF-002', 'NPB-XYZ-002');

        Volt::actingAs($this->user)->test('pages.transaksi.index')
            ->set('search', 'NPB-XYZ-001')
            ->assertSee('REF-001')
            ->assertDontSee('REF-002');
    }

    public function test_bisa_sort_by_tanggal(): void
    {
        Transaksi::create(['sekolah_id' => $this->sekolah->id, 'nomor_referensi_asal' => 'REF-LAMA', 'tanggal_npb' => '2026-01-01', 'status' => 'draft']);
        Transaksi::create(['sekolah_id' => $this->sekolah->id, 'nomor_referensi_asal' => 'REF-BARU', 'tanggal_npb' => '2026-03-01', 'status' => 'draft']);

        $component = Volt::actingAs($this->user)->test('pages.transaksi.index');
        // default desc -> REF-BARU duluan
        $component->assertSeeInOrder(['REF-BARU', 'REF-LAMA']);
    }

    /** Ini poin utama request: draft sekarang bisa diedit & dihapus langsung dari index, bukan cuma Word/PDF. */
    public function test_draft_punya_tombol_edit_dan_hapus(): void
    {
        $draft = $this->buatTransaksi('draft', 'REF-DRAFT');

        Volt::actingAs($this->user)->test('pages.transaksi.index')
            ->assertSeeHtml(route('transaksi.edit', $draft))
            ->assertSeeHtml('wire:click="mintaHapusSatuan(' . $draft->id . ')"');
    }

    public function test_selesai_juga_punya_tombol_edit_dan_hapus(): void
    {
        $selesai = $this->buatTransaksi('selesai', 'REF-SELESAI', 'NPB-001');

        Volt::actingAs($this->user)->test('pages.transaksi.index')
            ->assertSeeHtml(route('transaksi.edit', $selesai))
            ->assertSeeHtml('wire:click="mintaHapusSatuan(' . $selesai->id . ')"');
    }

    public function test_hapus_draft_langsung_dari_index_beneran_kehapus(): void
    {
        $draft = $this->buatTransaksi('draft', 'REF-DRAFT');

        Volt::actingAs($this->user)->test('pages.transaksi.index')
            ->call('mintaHapusSatuan', $draft->id)
            ->call('eksekusiHapus');

        $this->assertDatabaseMissing('transaksi', ['id' => $draft->id]);
    }

    public function test_badge_status_muncul_sesuai_status(): void
    {
        $this->buatTransaksi('draft', 'REF-DRAFT');
        $this->buatTransaksi('selesai', 'REF-SELESAI', 'NPB-001');

        $html = Volt::actingAs($this->user)->test('pages.transaksi.index')->html();

        $this->assertStringContainsString('Draft', $html);
        $this->assertStringContainsString('Selesai', $html);
    }

    public function test_pagination_pakai_view_custom(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            $this->buatTransaksi('draft', "REF-{$i}");
        }

        $html = Volt::actingAs($this->user)->test('pages.transaksi.index')->html();

        $this->assertStringNotContainsString('rounded-l-md', $html);
        $this->assertStringContainsString('Menampilkan', $html);
    }
}
