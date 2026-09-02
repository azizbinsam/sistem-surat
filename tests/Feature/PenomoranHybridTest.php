<?php

namespace Tests\Feature;

use App\Models\MasterBarang;
use App\Models\Sekolah;
use App\Models\TahunAnggaran;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\NomorSuratService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class PenomoranHybridTest extends TestCase
{
    use RefreshDatabase;

    protected Sekolah $sekolah;
    protected TahunAnggaran $tahunAnggaran;
    protected User $user;

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

        // Sekolah::booted() otomatis bikin 1 tahun anggaran default (status aktif) — pakai itu,
        // tinggal set nomor_urut_terakhir awalnya ke 5 buat skenario test.
        $this->tahunAnggaran = $this->sekolah->tahunAnggaran()->first();
        $this->tahunAnggaran->update(['nomor_urut_terakhir' => 5]);

        $this->user = User::factory()->create(['sekolah_id' => $this->sekolah->id]);
    }

    public function test_nomor_urut_terakhir_bisa_diedit_lewat_pengaturan_sekolah(): void
    {
        Volt::actingAs($this->user)
            ->test('pages.pengaturan.sekolah')
            ->set('nomor_urut_terakhir', 42)
            ->call('simpanProfil')
            ->assertHasNoErrors();

        $this->assertSame(42, $this->tahunAnggaran->fresh()->nomor_urut_terakhir);
    }

    public function test_nomor_urut_terakhir_wajib_angka_dan_tidak_boleh_negatif(): void
    {
        Volt::actingAs($this->user)
            ->test('pages.pengaturan.sekolah')
            ->set('nomor_urut_terakhir', -1)
            ->call('simpanProfil')
            ->assertHasErrors(['nomor_urut_terakhir']);

        // nilai lama tidak berubah
        $this->assertSame(5, $this->tahunAnggaran->fresh()->nomor_urut_terakhir);
    }

    public function test_edit_nomor_urut_terakhir_mempengaruhi_nomor_npb_berikutnya(): void
    {
        Volt::actingAs($this->user)
            ->test('pages.pengaturan.sekolah')
            ->set('nomor_urut_terakhir', 99)
            ->call('simpanProfil');

        $nomorService = app(NomorSuratService::class);
        $nomorBaru = $nomorService->generateNomorNpb($this->sekolah->fresh(), $this->tahunAnggaran->fresh(), now());

        $this->assertStringContainsString('/0100/', $nomorBaru);
    }

    /** Nomor urut milik Tahun Anggaran, jadi harus kejaga proteksi tenant-nya juga. */
    public function test_tidak_bisa_ngedit_nomor_urut_tahun_anggaran_sekolah_lain(): void
    {
        $sekolahLain = Sekolah::create([
            'nama_sekolah' => 'SDN Lain',
            'kode_sekolah' => 'SDNLAIN',
            'nama_pemerintah' => 'X',
            'nama_dinas' => 'Y',
            'alamat' => 'Z',
            'tempat' => 'W',
        ]);

        Volt::actingAs($this->user)
            ->test('pages.pengaturan.sekolah')
            ->set('nomor_urut_terakhir', 999)
            ->call('simpanProfil');

        // Tahun anggaran sekolah LAIN tidak boleh ikut ke-update
        $this->assertNotSame(999, $sekolahLain->tahunAnggaran()->first()->nomor_urut_terakhir);
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

        $counterSebelum = $this->tahunAnggaran->fresh()->nomor_urut_terakhir;

        // "Download ulang" — manggil generate() beneran lewat komponen index
        Volt::actingAs($this->user)
            ->test('pages.transaksi.index')
            ->call('generate', $transaksi->id, 'docx');

        $transaksi->refresh();

        // Nomor NPB/SPB/SPPB TETAP, dan counter TIDAK naik — generateNomorNpb() nggak dipanggil
        $this->assertSame('005/BOS/2019', $transaksi->nomor_npb);
        $this->assertSame('005/BOS/2019.1', $transaksi->nomor_spb);
        $this->assertSame($counterSebelum, $this->tahunAnggaran->fresh()->nomor_urut_terakhir);
    }

    /**
     * Regresi: generateBulk() sebelumnya query whereIn('id', $this->selected) tanpa
     * orderBy sama sekali, jadi urutan penomoran ngikutin urutan baris di DB (biasanya
     * id/insertion order) — bukan urutan tanggal transaksi. Kalau user nge-select
     * campuran transaksi dari beberapa halaman/urutan klik yang nggak kronologis,
     * transaksi bertanggal lebih baru bisa kebagian nomor urut lebih kecil daripada
     * yang tanggalnya lebih lama. Nomor NPB harus selalu ngikutin kronologis tanggal.
     */
    public function test_generate_bulk_kasih_nomor_urut_sesuai_kronologis_tanggal_bukan_urutan_select(): void
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

        $buatTransaksi = function (string $ref, string $tanggal) use ($pegawai, $barang) {
            $t = Transaksi::create([
                'sekolah_id' => $this->sekolah->id,
                'nomor_referensi_asal' => $ref,
                'tanggal_npb' => $tanggal,
                'pihak_peminta_id' => $pegawai->id,
                'status' => 'draft',
            ]);
            $t->items()->create([
                'master_barang_id' => $barang->id,
                'spesifikasi' => 'Standar',
                'jumlah' => 1,
                'satuan' => 'Rim',
                'keperluan' => 'x',
            ]);

            return $t;
        };

        // Sengaja dibikin TIDAK kronologis: yang tanggalnya paling baru dibuat duluan
        // (jadi id-nya paling kecil), yang paling lama dibuat belakangan.
        $terbaru = $buatTransaksi('REF-BARU', '2026-03-01');
        $terlama = $buatTransaksi('REF-LAMA', '2026-01-01');
        $tengah = $buatTransaksi('REF-TENGAH', '2026-02-01');

        // nomor_urut_terakhir mulai dari 5 (lihat setUp)
        Volt::actingAs($this->user)
            ->test('pages.transaksi.index')
            ->set('selected', [$terbaru->id, $terlama->id, $tengah->id]) // urutan select sengaja diacak
            ->call('generateBulk', 'docx');

        $this->assertStringContainsString('/0006/', $terlama->fresh()->nomor_npb); // paling lama -> nomor terkecil
        $this->assertStringContainsString('/0007/', $tengah->fresh()->nomor_npb);
        $this->assertStringContainsString('/0008/', $terbaru->fresh()->nomor_npb); // paling baru -> nomor terbesar
    }

    // ===== Batalkan Nomor Surat (satuan) =====

    public function test_batalkan_nomor_yang_terakhir_dikeluarkan_mengembalikan_counter(): void
    {
        $pegawai = \App\Models\Pegawai::create([
            'sekolah_id' => $this->sekolah->id, 'nama' => 'Budi', 'jabatan' => 'Guru', 'kategori' => 'guru',
        ]);
        $transaksi = Transaksi::create([
            'sekolah_id' => $this->sekolah->id,
            'nomor_referensi_asal' => 'REF-1',
            'tanggal_npb' => '2026-01-10',
            'pihak_peminta_id' => $pegawai->id,
            'status' => 'draft',
        ]);

        Volt::actingAs($this->user)->test('pages.transaksi.index')->call('generate', $transaksi->id, 'docx');

        $this->assertSame(6, $this->tahunAnggaran->fresh()->nomor_urut_terakhir);
        $this->assertStringContainsString('/0006/', $transaksi->fresh()->nomor_npb);

        Volt::actingAs($this->user)->test('pages.transaksi.index')
            ->call('mintaBatalkanNomor', $transaksi->id)
            ->assertSet('nomorBisaDikembalikan', true)
            ->call('eksekusiBatalkanNomor');

        $transaksi->refresh();
        $this->assertNull($transaksi->nomor_npb);
        $this->assertNull($transaksi->nomor_spb);
        $this->assertNull($transaksi->nomor_sppb);
        $this->assertSame('draft', $transaksi->status);
        $this->assertSame(5, $this->tahunAnggaran->fresh()->nomor_urut_terakhir);

        Volt::actingAs($this->user)->test('pages.transaksi.index')->call('generate', $transaksi->id, 'docx');
        $this->assertStringContainsString('/0006/', $transaksi->fresh()->nomor_npb);
    }

    public function test_batalkan_nomor_yang_bukan_terakhir_tidak_mengembalikan_counter(): void
    {
        $pegawai = \App\Models\Pegawai::create([
            'sekolah_id' => $this->sekolah->id, 'nama' => 'Budi', 'jabatan' => 'Guru', 'kategori' => 'guru',
        ]);
        $transaksiSalah = Transaksi::create([
            'sekolah_id' => $this->sekolah->id, 'nomor_referensi_asal' => 'REF-SALAH',
            'tanggal_npb' => '2026-01-10', 'pihak_peminta_id' => $pegawai->id, 'status' => 'draft',
        ]);
        $transaksiSetelahnya = Transaksi::create([
            'sekolah_id' => $this->sekolah->id, 'nomor_referensi_asal' => 'REF-SETELAHNYA',
            'tanggal_npb' => '2026-01-11', 'pihak_peminta_id' => $pegawai->id, 'status' => 'draft',
        ]);

        Volt::actingAs($this->user)->test('pages.transaksi.index')->call('generate', $transaksiSalah->id, 'docx'); // nomor 0006
        Volt::actingAs($this->user)->test('pages.transaksi.index')->call('generate', $transaksiSetelahnya->id, 'docx'); // nomor 0007

        Volt::actingAs($this->user)->test('pages.transaksi.index')
            ->call('mintaBatalkanNomor', $transaksiSalah->id)
            ->assertSet('nomorBisaDikembalikan', false)
            ->call('eksekusiBatalkanNomor');

        $this->assertNull($transaksiSalah->fresh()->nomor_npb);
        $this->assertSame(7, $this->tahunAnggaran->fresh()->nomor_urut_terakhir);
        $this->assertStringContainsString('/0007/', $transaksiSetelahnya->fresh()->nomor_npb);
    }

    // ===== Batalkan Nomor Surat (bulk) =====

    public function test_batalkan_nomor_bulk_untuk_beberapa_nomor_terakhir_sekaligus_reclaim_semua(): void
    {
        $pegawai = \App\Models\Pegawai::create([
            'sekolah_id' => $this->sekolah->id, 'nama' => 'Budi', 'jabatan' => 'Guru', 'kategori' => 'guru',
        ]);
        $t1 = Transaksi::create(['sekolah_id' => $this->sekolah->id, 'nomor_referensi_asal' => 'REF-1', 'tanggal_npb' => '2026-01-01', 'pihak_peminta_id' => $pegawai->id, 'status' => 'draft']);
        $t2 = Transaksi::create(['sekolah_id' => $this->sekolah->id, 'nomor_referensi_asal' => 'REF-2', 'tanggal_npb' => '2026-01-02', 'pihak_peminta_id' => $pegawai->id, 'status' => 'draft']);
        $t3 = Transaksi::create(['sekolah_id' => $this->sekolah->id, 'nomor_referensi_asal' => 'REF-3', 'tanggal_npb' => '2026-01-03', 'pihak_peminta_id' => $pegawai->id, 'status' => 'draft']);

        // Generate ketiganya berurutan -> jadi nomor 0006, 0007, 0008
        foreach ([$t1, $t2, $t3] as $t) {
            Volt::actingAs($this->user)->test('pages.transaksi.index')->call('generate', $t->id, 'docx');
        }
        $this->assertSame(8, $this->tahunAnggaran->fresh()->nomor_urut_terakhir);

        // Batalkan semuanya sekaligus, urutan select sengaja diacak
        Volt::actingAs($this->user)->test('pages.transaksi.index')
            ->set('selected', [$t2->id, $t1->id, $t3->id])
            ->call('mintaBatalkanNomorBulk')
            ->assertSet('ringkasanBatalkanNomorBulk.bisa', fn($bisa) => count($bisa) === 3)
            ->call('eksekusiBatalkanNomor');

        // Ketiganya kereclaim semua -> counter balik ke 5 (state sebelum digenerate)
        $this->assertSame(5, $this->tahunAnggaran->fresh()->nomor_urut_terakhir);
        $this->assertNull($t1->fresh()->nomor_npb);
        $this->assertNull($t2->fresh()->nomor_npb);
        $this->assertNull($t3->fresh()->nomor_npb);
        $this->assertSame('draft', $t1->fresh()->status);
    }

    public function test_batalkan_nomor_bulk_campuran_yang_bisa_dan_tidak_bisa_direclaim(): void
    {
        $pegawai = \App\Models\Pegawai::create([
            'sekolah_id' => $this->sekolah->id, 'nama' => 'Budi', 'jabatan' => 'Guru', 'kategori' => 'guru',
        ]);
        $t1 = Transaksi::create(['sekolah_id' => $this->sekolah->id, 'nomor_referensi_asal' => 'REF-1', 'tanggal_npb' => '2026-01-01', 'pihak_peminta_id' => $pegawai->id, 'status' => 'draft']);
        $t2 = Transaksi::create(['sekolah_id' => $this->sekolah->id, 'nomor_referensi_asal' => 'REF-2', 'tanggal_npb' => '2026-01-02', 'pihak_peminta_id' => $pegawai->id, 'status' => 'draft']);
        $t3TidakDipilih = Transaksi::create(['sekolah_id' => $this->sekolah->id, 'nomor_referensi_asal' => 'REF-3', 'tanggal_npb' => '2026-01-03', 'pihak_peminta_id' => $pegawai->id, 'status' => 'draft']);

        // Generate ketiganya -> 0006, 0007, 0008. Yang mau dibatalkan cuma t1 & t2,
        // t3 (nomor terakhir, 0008) SENGAJA nggak ikut dipilih -- jadi t2 (0007)
        // nggak bisa direclaim karena masih ada t3 (0008) di depannya.
        foreach ([$t1, $t2, $t3TidakDipilih] as $t) {
            Volt::actingAs($this->user)->test('pages.transaksi.index')->call('generate', $t->id, 'docx');
        }

        Volt::actingAs($this->user)->test('pages.transaksi.index')
            ->set('selected', [$t1->id, $t2->id])
            ->call('mintaBatalkanNomorBulk')
            ->assertSet('ringkasanBatalkanNomorBulk.bisa', [])
            ->assertSet('ringkasanBatalkanNomorBulk.tidak_bisa', fn($tb) => count($tb) === 2)
            ->call('eksekusiBatalkanNomor');

        // Counter TETAP 8 -- nggak ada yang direclaim, karena t3 (0008) masih ada
        $this->assertSame(8, $this->tahunAnggaran->fresh()->nomor_urut_terakhir);
        $this->assertNull($t1->fresh()->nomor_npb);
        $this->assertNull($t2->fresh()->nomor_npb);
        $this->assertStringContainsString('/0008/', $t3TidakDipilih->fresh()->nomor_npb); // nggak keganggu
    }

    // ===== Regresi: checkbox baris harus wire:model.live, bukan wire:model
    // deferred — kalau deferred, bulk bar (Hapus/Batalkan Terpilih) nggak
    // langsung muncul pas centang satu-satu, cuma nyala kalau klik "select all"
    // (karena "select all" pakai wire:click yang emang langsung ngerequest) =====

    public function test_checkbox_baris_pakai_wire_model_live_bukan_deferred(): void
    {
        $pegawai = \App\Models\Pegawai::create([
            'sekolah_id' => $this->sekolah->id, 'nama' => 'Budi', 'jabatan' => 'Guru', 'kategori' => 'guru',
        ]);
        Transaksi::create(['sekolah_id' => $this->sekolah->id, 'nomor_referensi_asal' => 'REF-1', 'tanggal_npb' => '2026-01-01', 'pihak_peminta_id' => $pegawai->id, 'status' => 'draft']);

        $html = Volt::actingAs($this->user)->test('pages.transaksi.index')->html();

        $this->assertStringContainsString('wire:model.live="selected"', $html);
        $this->assertStringNotContainsString('wire:model="selected"', $html);
    }
}