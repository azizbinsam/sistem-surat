<?php

namespace Tests\Feature;

use App\Models\MasterBarang;
use App\Models\Sekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class MasterBarangUniqueValidationTest extends TestCase
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

    public function test_create_tolak_kode_barang_yang_sudah_ada(): void
    {
        MasterBarang::create([
            'sekolah_id' => $this->sekolah->id,
            'kode_barang' => 'A1',
            'nama_barang' => 'Kertas',
            'satuan_default' => 'Rim',
        ]);

        Volt::actingAs($this->user)
            ->test('pages.master-barang.create')
            ->set('kode_barang', 'A1')
            ->set('nama_barang', 'Kertas Baru')
            ->set('satuan_default', 'Rim')
            ->call('simpan')
            ->assertHasErrors(['kode_barang' => 'unique']);

        $this->assertSame(1, MasterBarang::where('kode_barang', 'A1')->count());
    }

    public function test_create_boleh_pakai_kode_barang_yang_sudah_dihapus_soft_delete(): void
    {
        $lama = MasterBarang::create([
            'sekolah_id' => $this->sekolah->id,
            'kode_barang' => 'A1',
            'nama_barang' => 'Kertas Lama',
            'satuan_default' => 'Rim',
        ]);
        $lama->delete();

        Volt::actingAs($this->user)
            ->test('pages.master-barang.create')
            ->set('kode_barang', 'A1')
            ->set('nama_barang', 'Kertas Baru')
            ->set('satuan_default', 'Rim')
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('master_barang', ['kode_barang' => 'A1', 'nama_barang' => 'Kertas Baru', 'deleted_at' => null]);
    }

    public function test_edit_boleh_simpan_tanpa_ubah_kode_barang_sendiri(): void
    {
        $barang = MasterBarang::create([
            'sekolah_id' => $this->sekolah->id,
            'kode_barang' => 'A1',
            'nama_barang' => 'Kertas',
            'satuan_default' => 'Rim',
        ]);

        Volt::actingAs($this->user)
            ->test('pages.master-barang.edit', ['masterBarang' => $barang])
            ->set('nama_barang', 'Kertas Diedit')
            ->call('simpan')
            ->assertHasNoErrors();
    }

    public function test_edit_tolak_ganti_kode_barang_ke_yang_sudah_dipakai_barang_lain(): void
    {
        MasterBarang::create([
            'sekolah_id' => $this->sekolah->id,
            'kode_barang' => 'A1',
            'nama_barang' => 'Kertas',
            'satuan_default' => 'Rim',
        ]);
        $barangSaya = MasterBarang::create([
            'sekolah_id' => $this->sekolah->id,
            'kode_barang' => 'A2',
            'nama_barang' => 'Spidol',
            'satuan_default' => 'Buah',
        ]);

        Volt::actingAs($this->user)
            ->test('pages.master-barang.edit', ['masterBarang' => $barangSaya])
            ->set('kode_barang', 'A1')
            ->call('simpan')
            ->assertHasErrors(['kode_barang' => 'unique']);
    }

    public function test_kode_barang_yang_sama_boleh_dipakai_sekolah_lain(): void
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

        Volt::actingAs($this->user)
            ->test('pages.master-barang.create')
            ->set('kode_barang', 'A1')
            ->set('nama_barang', 'Punya Sekolah Saya')
            ->set('satuan_default', 'Rim')
            ->call('simpan')
            ->assertHasNoErrors();
    }
}
