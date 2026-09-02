<?php

namespace Tests\Feature;

use App\Models\MasterBarang;
use App\Models\Sekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class MasterBarangImportTest extends TestCase
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

    protected function buatFileExcel(array $rows): \Illuminate\Http\Testing\File
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(
            ['Kode Barang', 'Nama Barang', 'Kategori', 'Satuan Default', 'Spesifikasi Default'],
            null,
            'A1'
        );
        $sheet->fromArray($rows, null, 'A2');

        $path = tempnam(sys_get_temp_dir(), 'xlsx') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return \Illuminate\Http\UploadedFile::fake()->createWithContent('test.xlsx', file_get_contents($path));
    }

    public function test_import_normal_semua_barang_baru_kesimpan(): void
    {
        $file = $this->buatFileExcel([
            ['A1', 'Kertas HVS', 'ATK', 'Rim', ''],
            ['A2', 'Spidol', 'ATK', 'Buah', ''],
        ]);

        Volt::actingAs($this->user)
            ->test('pages.master-barang.import')
            ->set('file', $file)
            ->call('import');

        $this->assertDatabaseHas('master_barang', ['kode_barang' => 'A1', 'sekolah_id' => $this->sekolah->id]);
        $this->assertDatabaseHas('master_barang', ['kode_barang' => 'A2', 'sekolah_id' => $this->sekolah->id]);
        $this->assertSame(2, MasterBarang::count());
    }

    /** Ini kasus yang dilaporkan: barang yang udah dihapus (soft delete) nggak boleh nge-block re-import. */
    public function test_barang_yang_sudah_dihapus_soft_delete_boleh_diupload_ulang(): void
    {
        $lama = MasterBarang::create([
            'sekolah_id' => $this->sekolah->id,
            'kode_barang' => 'A1',
            'nama_barang' => 'Kertas Lama',
            'satuan_default' => 'Rim',
        ]);
        $lama->delete(); // soft delete

        $this->assertSoftDeleted('master_barang', ['id' => $lama->id]);

        $file = $this->buatFileExcel([
            ['A1', 'Kertas HVS Baru', 'ATK', 'Rim', ''],
        ]);

        Volt::actingAs($this->user)
            ->test('pages.master-barang.import')
            ->set('file', $file)
            ->call('import');

        // Barang baru dengan kode yang sama berhasil dibuat -> BUKAN dianggap "sudah ada"
        $this->assertDatabaseHas('master_barang', [
            'kode_barang' => 'A1',
            'nama_barang' => 'Kertas HVS Baru',
            'deleted_at' => null,
        ]);
    }

    public function test_kode_yang_sudah_ada_di_database_dilewati_tapi_baris_lain_tetap_masuk(): void
    {
        MasterBarang::create([
            'sekolah_id' => $this->sekolah->id,
            'kode_barang' => 'A1',
            'nama_barang' => 'Kertas Existing',
            'satuan_default' => 'Rim',
        ]);

        $file = $this->buatFileExcel([
            ['A1', 'Kertas HVS Duplikat', 'ATK', 'Rim', ''], // sudah ada -> harus di-skip
            ['A2', 'Spidol Baru', 'ATK', 'Buah', ''],        // baru -> harus masuk
        ]);

        Volt::actingAs($this->user)
            ->test('pages.master-barang.import')
            ->set('file', $file)
            ->call('import');

        // A1 TIDAK berubah/ke-duplikat, tetap data lama, cuma 1 baris
        $this->assertSame(1, MasterBarang::where('kode_barang', 'A1')->count());
        $this->assertDatabaseHas('master_barang', ['kode_barang' => 'A1', 'nama_barang' => 'Kertas Existing']);
        $this->assertDatabaseMissing('master_barang', ['nama_barang' => 'Kertas HVS Duplikat']);

        // A2 (barang baru, bukan duplikat) TETAP masuk meskipun ada baris lain yang di-skip
        $this->assertDatabaseHas('master_barang', ['kode_barang' => 'A2', 'nama_barang' => 'Spidol Baru']);
    }

    public function test_kode_duplikat_di_dalam_file_yang_sama_baris_pertama_yang_menang(): void
    {
        $file = $this->buatFileExcel([
            ['A1', 'Kertas Versi Pertama', 'ATK', 'Rim', ''],
            ['A1', 'Kertas Versi Kedua', 'ATK', 'Rim', ''], // duplikat dalam file yang sama
        ]);

        Volt::actingAs($this->user)
            ->test('pages.master-barang.import')
            ->set('file', $file)
            ->call('import');

        $this->assertSame(1, MasterBarang::where('kode_barang', 'A1')->count());
        $this->assertDatabaseHas('master_barang', ['kode_barang' => 'A1', 'nama_barang' => 'Kertas Versi Pertama']);
    }

    public function test_pesan_sukses_menyebutkan_jumlah_ditambahkan_dan_dilewati(): void
    {
        MasterBarang::create([
            'sekolah_id' => $this->sekolah->id,
            'kode_barang' => 'A1',
            'nama_barang' => 'Sudah Ada',
            'satuan_default' => 'Rim',
        ]);

        $file = $this->buatFileExcel([
            ['A1', 'Duplikat', 'ATK', 'Rim', ''],
            ['A2', 'Barang Baru', 'ATK', 'Buah', ''],
        ]);

        Volt::actingAs($this->user)
            ->test('pages.master-barang.import')
            ->set('file', $file)
            ->call('import');

        $this->assertStringContainsString('1 barang berhasil ditambahkan', session('success'));
        $this->assertStringContainsString('1 barang dilewati', session('success'));
        $this->assertStringContainsString('A1', session('success'));
    }

    public function test_data_sekolah_lain_tidak_menghalangi_kode_yang_sama(): void
    {
        $sekolahLain = Sekolah::create([
            'nama_sekolah' => 'SDN Lain',
            'kode_sekolah' => 'SDNLAIN',
            'nama_pemerintah' => 'X',
            'nama_dinas' => 'Y',
            'alamat' => 'Z',
            'tempat' => 'W',
        ]);
        MasterBarang::create([
            'sekolah_id' => $sekolahLain->id,
            'kode_barang' => 'A1',
            'nama_barang' => 'Punya Sekolah Lain',
            'satuan_default' => 'Rim',
        ]);

        $file = $this->buatFileExcel([
            ['A1', 'Punya Sekolah Saya', 'ATK', 'Rim', ''],
        ]);

        Volt::actingAs($this->user)
            ->test('pages.master-barang.import')
            ->set('file', $file)
            ->call('import');

        $this->assertDatabaseHas('master_barang', [
            'sekolah_id' => $this->sekolah->id,
            'kode_barang' => 'A1',
            'nama_barang' => 'Punya Sekolah Saya',
        ]);
    }

    public function test_baris_dengan_field_wajib_kosong_tetap_gagal(): void
    {
        $file = $this->buatFileExcel([
            ['', 'Tanpa Kode', 'ATK', 'Rim', ''],
        ]);

        Volt::actingAs($this->user)
            ->test('pages.master-barang.import')
            ->set('file', $file)
            ->call('import')
            ->assertSet('errorMsg', fn($v) => str_contains($v, 'Baris 2'));

        $this->assertDatabaseMissing('master_barang', ['nama_barang' => 'Tanpa Kode']);
    }
}
