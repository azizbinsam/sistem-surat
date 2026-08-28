<?php

namespace Tests\Feature;

use App\Models\BarangMasuk;
use App\Models\BarangMasukItem;
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

class TransaksiKeluarCrudTest extends TestCase
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
            'nama_pemerintah' => 'PEMKAB LEBAK',
            'nama_dinas' => 'DISDIK',
            'alamat' => 'Jl. Contoh',
            'tempat' => 'Rangkasbitung',
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

        $bpu = BarangMasuk::create(['sekolah_id' => $this->sekolah->id, 'nomor_bpu' => 'BPU-1', 'tanggal' => '2026-01-01']);
        BarangMasukItem::create(['barang_masuk_id' => $bpu->id, 'master_barang_id' => $this->barang->id, 'spesifikasi' => 'Standar', 'satuan' => 'Rim', 'jumlah' => 20]);
    }

    public function test_tambah_edit_hapus_dan_tab_filter(): void
    {
        // Tambah manual
        Volt::actingAs($this->user)
            ->test('pages.transaksi.create')
            ->set('nomor_referensi_asal', 'REF-001')
            ->set('tanggal_npb', '2026-02-01')
            ->set('pihak_peminta_id', $this->pegawai->id)
            ->set('items.0.master_barang_id', $this->barang->id)
            ->set('items.0.jumlah', 5)
            ->set('items.0.satuan', 'Rim')
            ->set('items.0.keperluan', 'Kebutuhan kelas')
            ->call('simpan')
            ->assertHasNoErrors();

        $transaksi = Transaksi::where('nomor_referensi_asal', 'REF-001')->firstOrFail();
        $this->assertSame('draft', $transaksi->status);
        $this->assertDatabaseHas('transaksi_item', ['transaksi_id' => $transaksi->id, 'jumlah' => 5]);

        // Simulasikan sudah "selesai" (nomor surat resmi terbit)
        $transaksi->update([
            'nomor_npb' => 'NPB-001',
            'nomor_spb' => 'NPB-001.1',
            'nomor_sppb' => 'NPB-001.2',
            'status' => 'selesai',
        ]);

        // Edit: ubah jumlah, nomor surat harus tetap sama
        Volt::actingAs($this->user)
            ->test('pages.transaksi.edit', ['transaksi' => $transaksi])
            ->set('items.0.jumlah', 8)
            ->call('simpan')
            ->assertHasNoErrors();

        $transaksi->refresh();
        $this->assertSame('NPB-001', $transaksi->nomor_npb);
        $this->assertSame('selesai', $transaksi->status);
        $this->assertDatabaseHas('transaksi_item', ['transaksi_id' => $transaksi->id, 'jumlah' => 8]);

        // Filter status: fitur tab udah dihapus (Fase 18), sekarang draft+selesai
        // tampil bareng dalam 1 list dengan badge, filter status pakai dropdown.
        Volt::actingAs($this->user)->test('pages.transaksi.index')
            ->set('filterStatus', 'draft')
            ->assertDontSee('REF-001'); // transaksi ini udah "selesai", jadi nggak nongol di filter draft

        Volt::actingAs($this->user)->test('pages.transaksi.index')
            ->set('filterStatus', 'selesai')
            ->assertSee('REF-001');

        // Hapus: barang harus balik ke stok (20 - 8 = 12, lalu balik ke 20)
        $service = app(PersediaanService::class);
        $this->assertSame(12, $service->sisaSaatIni($this->barang->id));

        Volt::actingAs($this->user)->test('pages.transaksi.index')
            ->call('hapus', $transaksi->id);

        $this->assertDatabaseMissing('transaksi', ['id' => $transaksi->id]);
        $this->assertSame(20, $service->sisaSaatIni($this->barang->id));
    }

    public function test_tidak_bisa_akses_transaksi_sekolah_lain(): void
    {
        $sekolahLain = Sekolah::create([
            'nama_sekolah' => 'SDN Lain',
            'kode_sekolah' => 'SDNLAIN',
            'nama_pemerintah' => 'X',
            'nama_dinas' => 'Y',
            'alamat' => 'Z',
            'tempat' => 'W',
        ]);
        $transaksiOrang = Transaksi::create([
            'sekolah_id' => $sekolahLain->id,
            'nomor_referensi_asal' => 'REF-ORANG',
            'tanggal_npb' => '2026-01-01',
            'status' => 'draft',
        ]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Volt::actingAs($this->user)->test('pages.transaksi.index')->call('hapus', $transaksiOrang->id);
    }
}