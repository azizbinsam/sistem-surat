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
            ->assertSet('errorMsg', null)
            // Bug regresi (Fase "Dashboard v2 + redesign"): tabel review sempat ke-timpa
            // jadi komentar placeholder kosong, jadi baris ini nggak pernah kelihatan di
            // HTML meskipun step & data internal-nya udah benar. Makanya perlu assertSee,
            // bukan cuma assertSet, biar bug kayak gini nggak lolos lagi diam-diam.
            ->assertSee('REF-01')
            ->assertSee('Kertas HVS')
            ->assertSee('pakai default'); // fallback text buat spesifikasi kosong
    }

    /**
     * Regresi: kolom transaksi_item.spesifikasi NOT NULL di DB, tapi baris review
     * sempat nyimpen null mentah-mentah kalau Excel-nya kosong -> SQLSTATE 23000.
     * Fallback berjenjang harus jalan: histori BPU terakhir -> spesifikasi_default
     * master barang -> nama_barang. Test lama cuma sampe step 'review' (nggak
     * pernah manggil simpan()), jadi bug ini nggak pernah ketauan sebelumnya.
     */
    public function test_spesifikasi_kosong_di_excel_fallback_ke_nama_barang_pas_simpan(): void
    {
        // $this->barang ("Kertas HVS") sengaja nggak punya spesifikasi_default
        // ataupun histori BPU sama sekali -> jatuh ke fallback paling akhir.
        $file = $this->buatFileExcel([
            ['2026-02-01', 'REF-KOSONG', 'Kertas HVS', '', 3, 'Rim', 'Kebutuhan kelas', '', '', ''],
        ]);

        Volt::actingAs($this->user)
            ->test('pages.transaksi.upload')
            ->set('file', $file)
            ->call('parse')
            ->call('simpan')
            ->assertHasNoErrors();

        $item = \App\Models\TransaksiItem::whereHas('transaksi', fn($q) => $q->where('nomor_referensi_asal', 'REF-KOSONG'))->firstOrFail();

        $this->assertSame('Kertas HVS', $item->spesifikasi);
    }

    public function test_spesifikasi_kosong_di_excel_fallback_ke_spesifikasi_default_master_barang(): void
    {
        $this->barang->update(['spesifikasi_default' => '80gsm A4']);

        $file = $this->buatFileExcel([
            ['2026-02-01', 'REF-DEFAULT', 'Kertas HVS', '', 3, 'Rim', 'Kebutuhan kelas', '', '', ''],
        ]);

        Volt::actingAs($this->user)
            ->test('pages.transaksi.upload')
            ->set('file', $file)
            ->call('parse')
            ->call('simpan')
            ->assertHasNoErrors();

        $item = \App\Models\TransaksiItem::whereHas('transaksi', fn($q) => $q->where('nomor_referensi_asal', 'REF-DEFAULT'))->firstOrFail();

        $this->assertSame('80gsm A4', $item->spesifikasi);
    }

    public function test_spesifikasi_kosong_di_excel_fallback_ke_histori_penerimaan_barang(): void
    {
        $this->barang->update(['spesifikasi_default' => '80gsm A4']); // ada default, tapi histori BPU harus menang

        $bpu = \App\Models\BarangMasuk::create([
            'sekolah_id' => $this->sekolah->id,
            'nomor_bpu' => 'BPU-001',
            'tanggal' => '2026-01-15',
        ]);
        \App\Models\BarangMasukItem::create([
            'barang_masuk_id' => $bpu->id,
            'master_barang_id' => $this->barang->id,
            'spesifikasi' => '70gsm F4 dari BPU',
            'satuan' => 'Rim',
            'jumlah' => 10,
        ]);

        $file = $this->buatFileExcel([
            ['2026-02-01', 'REF-HISTORI', 'Kertas HVS', '', 3, 'Rim', 'Kebutuhan kelas', '', '', ''],
        ]);

        Volt::actingAs($this->user)
            ->test('pages.transaksi.upload')
            ->set('file', $file)
            ->call('parse')
            ->call('simpan')
            ->assertHasNoErrors();

        $item = \App\Models\TransaksiItem::whereHas('transaksi', fn($q) => $q->where('nomor_referensi_asal', 'REF-HISTORI'))->firstOrFail();

        $this->assertSame('70gsm F4 dari BPU', $item->spesifikasi);
    }

    public function test_barang_belum_dikenal_tampil_dengan_dropdown_mapping_di_review(): void
    {
        $file = $this->buatFileExcel([
            ['2026-02-01', 'REF-02', 'Barang Yang Belum Ada', 'Standar', 5, 'Buah', 'Kebutuhan kelas', '', '', ''],
        ]);

        Volt::actingAs($this->user)
            ->test('pages.transaksi.upload')
            ->set('file', $file)
            ->call('parse')
            ->assertSee('Barang Yang Belum Ada')
            ->assertSee('Belum dipilih') // opsi default dropdown mapping
            ->assertSee('atau buat sebagai barang baru');
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
