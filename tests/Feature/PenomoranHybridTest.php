<?php

namespace Tests\Feature;

use App\Models\MasterBarang;
use App\Models\Sekolah;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\NomorSuratService;
use App\Services\SuratWordGenerator;
use App\Services\SuratPdfGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class PenomoranHybridTest extends TestCase
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
            'nama_pemerintah' => 'PEMKAB LEBAK',
            'nama_dinas' => 'DISDIK',
            'alamat' => 'Jl. Contoh',
            'tempat' => 'Rangkasbitung',
            'nomor_urut_terakhir' => 5,
        ]);
        $this->user = User::factory()->create(['sekolah_id' => $this->sekolah->id]);
    }

    public function test_nomor_urut_terakhir_bisa_diedit_lewat_pengaturan_sekolah(): void
    {
        Volt::actingAs($this->user)
            ->test('pages.pengaturan.sekolah')
            ->set('nomor_urut_terakhir', 42)
            ->call('simpanProfil')
            ->assertHasNoErrors();

        $this->assertSame(42, $this->sekolah->fresh()->nomor_urut_terakhir);
    }

    public function test_nomor_urut_terakhir_wajib_angka_dan_tidak_boleh_negatif(): void
    {
        Volt::actingAs($this->user)
            ->test('pages.pengaturan.sekolah')
            ->set('nomor_urut_terakhir', -1)
            ->call('simpanProfil')
            ->assertHasErrors(['nomor_urut_terakhir']);

        // nilai lama tidak berubah
        $this->assertSame(5, $this->sekolah->fresh()->nomor_urut_terakhir);
    }

    public function test_edit_nomor_urut_terakhir_mempengaruhi_nomor_npb_berikutnya(): void
    {
        Volt::actingAs($this->user)
            ->test('pages.pengaturan.sekolah')
            ->set('nomor_urut_terakhir', 99)
            ->call('simpanProfil');

        $nomorService = app(NomorSuratService::class);
        $nomorBaru = $nomorService->generateNomorNpb($this->sekolah->fresh(), now());

        $this->assertStringContainsString('/0100/', $nomorBaru);
    }

    public function test_generate_surat_skip_auto_nomor_kalau_nomor_npb_sudah_diisi_manual(): void
    {
        $pegawai = \App\Models\Pegawai::create([
            'sekolah_id' => $this->sekolah->id,
            'nama' => 'Budi',
            'nip' => '123',
            'jabatan' => 'Guru',
            'kategori' => 'guru',
        ]);
        $barang = MasterBarang::create([
            'sekolah_id' => $this->sekolah->id,
            'kode_barang' => 'ATK-001',
            'nama_barang' => 'Kertas HVS',
            'satuan_default' => 'Rim',
        ]);

        // Transaksi yang nomor_npb-nya udah diisi manual (mis. dari import historis)
        $transaksi = Transaksi::create([
            'sekolah_id' => $this->sekolah->id,
            'nomor_referensi_asal' => 'REF-HIST',
            'nomor_npb' => '005/BOS/2019',
            'nomor_spb' => '005/BOS/2019.1',
            'nomor_sppb' => '005/BOS/2019.2',
            'tanggal_npb' => '2026-01-01',
            'tanggal_spb' => '2026-01-01',
            'tanggal_sppb' => '2026-01-01',
            'pihak_peminta_id' => $pegawai->id,
            'status' => 'selesai',
        ]);
        $transaksi->items()->create([
            'master_barang_id' => $barang->id,
            'spesifikasi' => 'Standar',
            'jumlah' => 1,
            'satuan' => 'Rim',
            'keperluan' => 'x',
        ]);

        $sekolahSebelum = $this->sekolah->fresh()->nomor_urut_terakhir;

        // "Download ulang" — manggil generate() beneran lewat komponen index
        Volt::actingAs($this->user)
            ->test('pages.transaksi.index')
            ->call('generate', $transaksi->id, 'docx');

        $transaksi->refresh();

        // Nomor NPB/SPB/SPPB TETAP, dan counter TIDAK naik — generateNomorNpb() nggak dipanggil
        $this->assertSame('005/BOS/2019', $transaksi->nomor_npb);
        $this->assertSame('005/BOS/2019.1', $transaksi->nomor_spb);
        $this->assertSame($sekolahSebelum, $this->sekolah->fresh()->nomor_urut_terakhir);
    }
}
