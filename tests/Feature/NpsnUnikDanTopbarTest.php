<?php

namespace Tests\Feature;

use App\Models\Sekolah;
use App\Models\TahunAnggaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class NpsnUnikDanTopbarTest extends TestCase
{
    use RefreshDatabase;

    public function test_lengkapi_profil_tolak_kode_sekolah_yang_sudah_dipakai(): void
    {
        Sekolah::create([
            'nama_sekolah' => 'SDN Duluan', 'kode_sekolah' => 'SDN3RKST',
            'nama_pemerintah' => 'X', 'nama_dinas' => 'Y', 'alamat' => 'Z', 'tempat' => 'W',
        ]);

        $user = User::factory()->create(['sekolah_id' => null]);

        Volt::actingAs($user)
            ->test('pages.onboarding.lengkapi-profil')
            ->set('nama_sekolah', 'SDN Belakangan')
            ->set('kode_sekolah', 'SDN3RKST')
            ->set('nama_pemerintah', 'A')
            ->set('nama_dinas', 'B')
            ->set('alamat', 'C')
            ->set('tempat', 'D')
            ->call('simpan')
            ->assertHasErrors(['kode_sekolah' => 'unique']);

        $user->refresh();
        $this->assertNull($user->sekolah_id);
    }

    public function test_lengkapi_profil_boleh_pakai_kode_sekolah_yang_belum_dipakai(): void
    {
        $user = User::factory()->create(['sekolah_id' => null]);

        Volt::actingAs($user)
            ->test('pages.onboarding.lengkapi-profil')
            ->set('nama_sekolah', 'SDN Baru')
            ->set('kode_sekolah', 'SDNBARU1')
            ->set('nama_pemerintah', 'A')
            ->set('nama_dinas', 'B')
            ->set('alamat', 'C')
            ->set('tempat', 'D')
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('sekolah', ['kode_sekolah' => 'SDNBARU1']);
    }

    public function test_pengaturan_sekolah_boleh_simpan_tanpa_ubah_kode_sekolah_sendiri(): void
    {
        $sekolah = Sekolah::create([
            'nama_sekolah' => 'SDN Sendiri', 'kode_sekolah' => 'SDNSENDIRI',
            'nama_pemerintah' => 'X', 'nama_dinas' => 'Y', 'alamat' => 'Z', 'tempat' => 'W',
        ]);
        $user = User::factory()->create(['sekolah_id' => $sekolah->id]);

        Volt::actingAs($user)
            ->test('pages.pengaturan.sekolah')
            ->call('simpanProfil')
            ->assertHasNoErrors();
    }

    public function test_pengaturan_sekolah_tolak_ganti_kode_sekolah_ke_yang_sudah_dipakai_orang_lain(): void
    {
        Sekolah::create([
            'nama_sekolah' => 'SDN Lain', 'kode_sekolah' => 'SDNLAIN',
            'nama_pemerintah' => 'X', 'nama_dinas' => 'Y', 'alamat' => 'Z', 'tempat' => 'W',
        ]);
        $sekolahSaya = Sekolah::create([
            'nama_sekolah' => 'SDN Saya', 'kode_sekolah' => 'SDNSAYA',
            'nama_pemerintah' => 'X', 'nama_dinas' => 'Y', 'alamat' => 'Z', 'tempat' => 'W',
        ]);
        $user = User::factory()->create(['sekolah_id' => $sekolahSaya->id]);

        Volt::actingAs($user)
            ->test('pages.pengaturan.sekolah')
            ->set('kode_sekolah', 'SDNLAIN')
            ->call('simpanProfil')
            ->assertHasErrors(['kode_sekolah' => 'unique']);
    }

    public function test_topbar_menampilkan_daftar_tahun_anggaran_dan_bisa_pindah(): void
    {
        $sekolah = Sekolah::create([
            'nama_sekolah' => 'SDN 3 Rangkasbitung Timur', 'kode_sekolah' => 'SDN3RKST',
            'nama_pemerintah' => 'X', 'nama_dinas' => 'Y', 'alamat' => 'Z', 'tempat' => 'W',
        ]);
        $ta2026 = $sekolah->tahunAnggaran()->first();
        $ta2026->update(['is_aktif' => false]);
        $ta2027 = TahunAnggaran::create(['sekolah_id' => $sekolah->id, 'tahun' => 2027, 'is_aktif' => true]);

        $user = User::factory()->create(['sekolah_id' => $sekolah->id]);

        Volt::actingAs($user)
            ->test('layout.topbar')
            ->assertSee('2027')
            ->assertSee('2026')
            ->call('pilihTahunAnggaran', $ta2026->id)
            ->assertRedirect(route('dashboard'));

        $this->assertSame($ta2026->id, session('tahun_anggaran_id'));
    }

    public function test_topbar_tolak_pindah_ke_tahun_anggaran_sekolah_lain(): void
    {
        $sekolah = Sekolah::create([
            'nama_sekolah' => 'SDN Saya', 'kode_sekolah' => 'SDNSAYA',
            'nama_pemerintah' => 'X', 'nama_dinas' => 'Y', 'alamat' => 'Z', 'tempat' => 'W',
        ]);
        $sekolahLain = Sekolah::create([
            'nama_sekolah' => 'SDN Lain', 'kode_sekolah' => 'SDNLAIN',
            'nama_pemerintah' => 'X', 'nama_dinas' => 'Y', 'alamat' => 'Z', 'tempat' => 'W',
        ]);
        $taOrang = $sekolahLain->tahunAnggaran()->first();

        $user = User::factory()->create(['sekolah_id' => $sekolah->id]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Volt::actingAs($user)
            ->test('layout.topbar')
            ->call('pilihTahunAnggaran', $taOrang->id);
    }

    public function test_topbar_tidak_crash_buat_user_yang_belum_punya_sekolah(): void
    {
        // Skenario onboarding: user baru daftar, belum lengkapi profil sekolah.
        $user = User::factory()->create(['sekolah_id' => null]);

        Volt::actingAs($user)
            ->test('layout.topbar')
            ->assertOk()
            ->assertSee('Belum ada tahun anggaran');
    }

    public function test_topbar_ada_link_pengaturan_sekolah_dan_tombol_logout(): void
    {
        $sekolah = Sekolah::create([
            'nama_sekolah' => 'SDN 3 Rangkasbitung Timur', 'kode_sekolah' => 'SDN3RKST',
            'nama_pemerintah' => 'X', 'nama_dinas' => 'Y', 'alamat' => 'Z', 'tempat' => 'W',
        ]);
        $user = User::factory()->create(['sekolah_id' => $sekolah->id]);

        Volt::actingAs($user)
            ->test('layout.topbar')
            ->assertSee('Pengaturan Sekolah')
            ->assertSeeHtml(route('pengaturan.sekolah'))
            ->assertSeeText('Log Out');
    }

    public function test_logout_dari_topbar_beneran_logout(): void
    {
        $sekolah = Sekolah::create([
            'nama_sekolah' => 'SDN 3 Rangkasbitung Timur', 'kode_sekolah' => 'SDN3RKST',
            'nama_pemerintah' => 'X', 'nama_dinas' => 'Y', 'alamat' => 'Z', 'tempat' => 'W',
        ]);
        $user = User::factory()->create(['sekolah_id' => $sekolah->id]);

        Volt::actingAs($user)
            ->test('layout.topbar')
            ->call('logout');

        $this->assertGuest();
    }
}