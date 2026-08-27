<?php

namespace Tests\Feature;

use App\Models\MasterBarang;
use App\Models\Sekolah;
use App\Models\TahunAnggaran;
use App\Models\User;
use App\Services\TahunAnggaranResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TahunAnggaranScopingTest extends TestCase
{
    use RefreshDatabase;

    protected Sekolah $sekolah;
    protected User $user;
    protected TahunAnggaran $ta2026;
    protected TahunAnggaran $ta2027;

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

        // Sekolah::booted() otomatis bikin tahun anggaran 2026 (is_aktif=true)
        $this->ta2026 = $this->sekolah->tahunAnggaran()->first();

        // Simulasikan superadmin "buka tahun anggaran baru" 2027 (Fase 20 nanti)
        $this->ta2026->update(['is_aktif' => false]);
        $this->ta2027 = TahunAnggaran::create([
            'sekolah_id' => $this->sekolah->id,
            'tahun' => 2027,
            'nomor_urut_terakhir' => 0,
            'is_aktif' => true,
        ]);

        $this->user = User::factory()->create(['sekolah_id' => $this->sekolah->id]);
    }

    public function test_sekolah_baru_otomatis_dapat_1_tahun_anggaran_aktif(): void
    {
        $sekolahBaru = Sekolah::create([
            'nama_sekolah' => 'SDN Baru',
            'kode_sekolah' => 'SDNBARU',
            'nama_pemerintah' => 'X',
            'nama_dinas' => 'Y',
            'alamat' => 'Z',
            'tempat' => 'W',
        ]);

        $this->assertSame(1, $sekolahBaru->tahunAnggaran()->count());
        $this->assertTrue($sekolahBaru->tahunAnggaran()->first()->is_aktif);
    }

    public function test_create_master_barang_otomatis_masuk_tahun_anggaran_aktif(): void
    {
        $this->actingAs($this->user);

        $barang = MasterBarang::create([
            'sekolah_id' => $this->sekolah->id,
            'kode_barang' => 'A1',
            'nama_barang' => 'Kertas',
            'satuan_default' => 'Rim',
        ]);

        $this->assertSame($this->ta2027->id, $barang->tahun_anggaran_id);
    }

    public function test_query_master_barang_otomatis_kefilter_ke_tahun_anggaran_aktif(): void
    {
        MasterBarang::create([
            'sekolah_id' => $this->sekolah->id,
            'tahun_anggaran_id' => $this->ta2026->id,
            'kode_barang' => 'LAMA',
            'nama_barang' => 'Barang 2026',
            'satuan_default' => 'Rim',
        ]);
        MasterBarang::create([
            'sekolah_id' => $this->sekolah->id,
            'tahun_anggaran_id' => $this->ta2027->id,
            'kode_barang' => 'BARU',
            'nama_barang' => 'Barang 2027',
            'satuan_default' => 'Rim',
        ]);

        $this->actingAs($this->user);

        // Tahun anggaran aktif sekarang 2027 -> cuma barang 2027 yang kelihatan
        $hasil = MasterBarang::where('sekolah_id', $this->sekolah->id)->get();

        $this->assertCount(1, $hasil);
        $this->assertSame('BARU', $hasil->first()->kode_barang);
    }

    public function test_pindah_tahun_anggaran_via_resolver_mengubah_hasil_query(): void
    {
        MasterBarang::create([
            'sekolah_id' => $this->sekolah->id,
            'tahun_anggaran_id' => $this->ta2026->id,
            'kode_barang' => 'LAMA',
            'nama_barang' => 'Barang 2026',
            'satuan_default' => 'Rim',
        ]);

        $this->actingAs($this->user);

        // Defaultnya lihat 2027 (is_aktif) -> data 2026 nggak kelihatan
        $this->assertCount(0, MasterBarang::where('sekolah_id', $this->sekolah->id)->get());

        // Pindah ke 2026 lewat resolver (simulasi dropdown Fase 16)
        app(TahunAnggaranResolver::class)->pilih($this->ta2026, $this->sekolah);

        $hasil = MasterBarang::where('sekolah_id', $this->sekolah->id)->get();
        $this->assertCount(1, $hasil);
        $this->assertSame('LAMA', $hasil->first()->kode_barang);
    }

    public function test_resolver_tolak_pilih_tahun_anggaran_milik_sekolah_lain(): void
    {
        $sekolahLain = Sekolah::create([
            'nama_sekolah' => 'SDN Lain',
            'kode_sekolah' => 'SDNLAIN',
            'nama_pemerintah' => 'X',
            'nama_dinas' => 'Y',
            'alamat' => 'Z',
            'tempat' => 'W',
        ]);
        $taOrang = $sekolahLain->tahunAnggaran()->first();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        app(TahunAnggaranResolver::class)->pilih($taOrang, $this->sekolah);
    }

    public function test_query_tanpa_auth_tidak_kefilter_tahun_anggaran(): void
    {
        // Konteks tanpa login (console/seeder) — scope di-skip, semua kelihatan
        MasterBarang::create([
            'sekolah_id' => $this->sekolah->id,
            'tahun_anggaran_id' => $this->ta2026->id,
            'kode_barang' => 'LAMA',
            'nama_barang' => 'Barang 2026',
            'satuan_default' => 'Rim',
        ]);
        MasterBarang::create([
            'sekolah_id' => $this->sekolah->id,
            'tahun_anggaran_id' => $this->ta2027->id,
            'kode_barang' => 'BARU',
            'nama_barang' => 'Barang 2027',
            'satuan_default' => 'Rim',
        ]);

        $this->assertCount(2, MasterBarang::where('sekolah_id', $this->sekolah->id)->get());
    }
}
