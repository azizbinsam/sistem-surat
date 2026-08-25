<?php

namespace Tests\Feature;

use App\Models\MasterBarang;
use App\Models\Pegawai;
use App\Models\Sekolah;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class TransaksiKeluarUploadKolomBaruTest extends TestCase
{
    use RefreshDatabase;

    protected Sekolah $sekolah;
    protected User $user;
    protected MasterBarang $barang;

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
    }

    protected function buatFileExcel(array $rows): \Illuminate\Http\Testing\File
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(
            ['Tanggal', 'Nomor Referensi', 'Nama Barang', 'Spesifikasi', 'Jumlah', 'Satuan', 'Keperluan', 'Nama Peminta', 'Jabatan Peminta', 'Nomor NPB'],
            null,
            'A1'
        );
        $sheet->fromArray($rows, null, 'A2');

        $path = tempnam(sys_get_temp_dir(), 'xlsx') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return \Illuminate\Http\UploadedFile::fake()->createWithContent('test.xlsx', file_get_contents($path));
    }

    public function test_spesifikasi_boleh_kosong_di_excel(): void
    {
        $file = $this->buatFileExcel([
            ['2026-02-01', 'REF-01', 'Kertas HVS', '', 3, 'Rim', 'Kebutuhan kelas', '', '', ''],
        ]);

        Volt::actingAs($this->user)
            ->test('pages.transaksi.upload')
            ->set('file', $file)
            ->call('parse')
            ->assertSet('step', 'review')
            ->assertSet('errorMsg', null);
    }

    public function test_auto_mapping_pihak_peminta_dari_nama_dan_jabatan(): void
    {
        $pegawai = Pegawai::create([
            'sekolah_id' => $this->sekolah->id,
            'nama' => 'Siti Aminah',
            'nip' => '111',
            'jabatan' => 'Guru Kelas',
            'kategori' => 'guru',
        ]);

        $file = $this->buatFileExcel([
            ['2026-02-01', 'REF-02', 'Kertas HVS', 'Standar', 3, 'Rim', 'Kebutuhan kelas', 'Siti Aminah', 'Guru Kelas', ''],
        ]);

        Volt::actingAs($this->user)
            ->test('pages.transaksi.upload')
            ->set('file', $file)
            ->call('parse')
            ->call('simpan');

        $transaksi = Transaksi::where('nomor_referensi_asal', 'REF-02')->firstOrFail();
        $this->assertSame($pegawai->id, $transaksi->pihak_peminta_id);
    }

    public function test_nama_peminta_tidak_cocok_terdeteksi_dan_transaksi_tetap_tersimpan_tanpa_mapping(): void
    {
        $file = $this->buatFileExcel([
            ['2026-02-01', 'REF-03', 'Kertas HVS', 'Standar', 3, 'Rim', 'Kebutuhan kelas', 'Nama Yang Salah Ketik', 'Guru Kelas', ''],
        ]);

        Volt::actingAs($this->user)
            ->test('pages.transaksi.upload')
            ->set('file', $file)
            ->call('parse')
            ->assertSet('notifGagalMappingPeminta', ['REF-03']);

        $this->assertDatabaseMissing('transaksi', ['nomor_referensi_asal' => 'REF-03']); // belum simpan
    }

    public function test_nama_peminta_beda_beda_dalam_satu_referensi_kena_warning(): void
    {
        $file = $this->buatFileExcel([
            ['2026-02-01', 'REF-04', 'Kertas HVS', 'Standar', 3, 'Rim', 'Kebutuhan kelas', 'Budi', 'Guru', ''],
            ['2026-02-01', 'REF-04', 'Kertas HVS', 'Standar', 2, 'Rim', 'Kebutuhan kelas', 'Siti', 'Guru', ''],
        ]);

        Volt::actingAs($this->user)
            ->test('pages.transaksi.upload')
            ->set('file', $file)
            ->call('parse')
            ->assertSet('notifNamaBerbeda', fn($v) => count($v) === 1 && str_contains($v[0], 'REF-04'));
    }

    public function test_nomor_npb_override_angka_saja_otomatis_dilengkapi_format_standar(): void
    {
        $sekolahSebelum = $this->sekolah->fresh()->nomor_urut_terakhir;

        $file = $this->buatFileExcel([
            ['2026-01-15', 'REF-HIST', 'Kertas HVS', 'Standar', 3, 'Rim', 'Kebutuhan kelas', '', '', '0012'],
        ]);

        Volt::actingAs($this->user)
            ->test('pages.transaksi.upload')
            ->set('file', $file)
            ->call('parse')
            ->call('simpan');

        $transaksi = Transaksi::where('nomor_referensi_asal', 'REF-HIST')->firstOrFail();

        $this->assertSame('000.2.3.1/0012/NPB-SDN3RKST/I/2026', $transaksi->nomor_npb);
        $this->assertSame('000.2.3.1/0012.1/SPB-SDN3RKST/I/2026', $transaksi->nomor_spb);
        $this->assertSame('000.2.3.1/0012.2/SPPB-SDN3RKST/I/2026', $transaksi->nomor_sppb);
        $this->assertSame('selesai', $transaksi->status);

        // counter nomor_urut_terakhir TIDAK ikut naik gara-gara nomor override
        $this->assertSame($sekolahSebelum, $this->sekolah->fresh()->nomor_urut_terakhir);
    }

    public function test_nomor_npb_override_lengkap_dengan_format_lama_dipakai_apa_adanya(): void
    {
        $file = $this->buatFileExcel([
            ['2026-01-15', 'REF-LAMA', 'Kertas HVS', 'Standar', 3, 'Rim', 'Kebutuhan kelas', '', '', '005/BOS/2019'],
        ]);

        Volt::actingAs($this->user)
            ->test('pages.transaksi.upload')
            ->set('file', $file)
            ->call('parse')
            ->call('simpan');

        $transaksi = Transaksi::where('nomor_referensi_asal', 'REF-LAMA')->firstOrFail();

        // Format lama yang beda dari standar sekarang -> dipakai persis apa adanya
        $this->assertSame('005/BOS/2019', $transaksi->nomor_npb);
    }

    public function test_tanpa_nomor_npb_override_tetap_draft_seperti_biasa(): void
    {
        $file = $this->buatFileExcel([
            ['2026-02-01', 'REF-05', 'Kertas HVS', 'Standar', 3, 'Rim', 'Kebutuhan kelas', '', '', ''],
        ]);

        Volt::actingAs($this->user)
            ->test('pages.transaksi.upload')
            ->set('file', $file)
            ->call('parse')
            ->call('simpan');

        $transaksi = Transaksi::where('nomor_referensi_asal', 'REF-05')->firstOrFail();
        $this->assertSame('draft', $transaksi->status);
        $this->assertNull($transaksi->nomor_npb);
    }
}
