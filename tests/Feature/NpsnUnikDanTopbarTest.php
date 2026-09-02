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
            'nama_sekolah' => 'SDN Duluan',
            'kode_sekolah' => 'SDN3RKST',
            'npsn' => '20601936',
            'nama_pemerintah' => 'X',
            'nama_dinas' => 'Y',
            'alamat' => 'Z',
            'tempat' => 'W',
        ]);

        $user = User::factory()->create(['sekolah_id' => null]);

        Volt::actingAs($user)
            ->test('pages.onboarding.lengkapi-profil')
            ->set('nama_sekolah', 'SDN Belakangan')
            ->set('kode_sekolah', 'SDN3RKST')
            ->set('npsn', '20601936')
            ->set('nama_pemerintah', 'A')
            ->set('nama_dinas', 'B')
            ->set('alamat', 'C')
            ->set('tempat', 'D')
            ->call('simpan')
            ->assertHasErrors(['npsn' => 'unique']);

        $user->refresh();
        $this->assertNull($user->sekolah_id);
    }

    public function test_lengkapi_profil_boleh_pakai_kode_sekolah_yang_sama_asal_npsn_beda(): void
    {
        // Ini kasus intinya: kode_sekolah cuma singkatan buat nomor surat, banyak sekolah
        // wajar punya kode yang sama atau mirip. Yang harus unik itu NPSN.
        Sekolah::create([
            'nama_sekolah' => 'SDN Duluan',
            'kode_sekolah' => 'SDN3RKST',
            'npsn' => '20601936',
            'nama_pemerintah' => 'X',
            'nama_dinas' => 'Y',
            'alamat' => 'Z',
            'tempat' => 'W',
        ]);

        $user = User::factory()->create(['sekolah_id' => null]);

        Volt::actingAs($user)
            ->test('pages.onboarding.lengkapi-profil')
            ->set('nama_sekolah', 'SDN Belakangan')
            ->set('kode_sekolah', 'SDN3RKST')
            ->set('npsn', '20601937')
            ->set('nama_pemerintah', 'A')
            ->set('nama_dinas', 'B')
            ->set('alamat', 'C')
            ->set('tempat', 'D')
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('sekolah', ['kode_sekolah' => 'SDN3RKST', 'npsn' => '20601937']);
    }

    public function test_lengkapi_profil_boleh_pakai_npsn_yang_belum_dipakai(): void
    {
        $user = User::factory()->create(['sekolah_id' => null]);

        Volt::actingAs($user)
            ->test('pages.onboarding.lengkapi-profil')
            ->set('nama_sekolah', 'SDN Baru')
            ->set('kode_sekolah', 'SDNBARU1')
            ->set('npsn', '30601111')
            ->set('nama_pemerintah', 'A')
            ->set('nama_dinas', 'B')
            ->set('alamat', 'C')
            ->set('tempat', 'D')
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('sekolah', ['kode_sekolah' => 'SDNBARU1', 'npsn' => '30601111']);
    }

    public function test_pengaturan_sekolah_boleh_simpan_tanpa_ubah_data_sendiri(): void
    {
        $sekolah = Sekolah::create([
            'nama_sekolah' => 'SDN Sendiri',
            'kode_sekolah' => 'SDNSENDIRI',
            'npsn' => '20601111',
            'nama_pemerintah' => 'X',
            'nama_dinas' => 'Y',
            'alamat' => 'Z',
            'tempat' => 'W',
        ]);
        $user = User::factory()->create(['sekolah_id' => $sekolah->id]);

        Volt::actingAs($user)
            ->test('pages.pengaturan.sekolah')
            ->call('simpanProfil')
            ->assertHasNoErrors();
    }

    public function test_pengaturan_sekolah_boleh_ganti_kode_sekolah_ke_yang_dipakai_sekolah_lain(): void
    {
        Sekolah::create([
            'nama_sekolah' => 'SDN Lain',
            'kode_sekolah' => 'SDNLAIN',
            'npsn' => '20601222',
            'nama_pemerintah' => 'X',
            'nama_dinas' => 'Y',
            'alamat' => 'Z',
            'tempat' => 'W',
        ]);
        $sekolahSaya = Sekolah::create([
            'nama_sekolah' => 'SDN Saya',
            'kode_sekolah' => 'SDNSAYA',
            'npsn' => '20601333',
            'nama_pemerintah' => 'X',
            'nama_dinas' => 'Y',
            'alamat' => 'Z',
            'tempat' => 'W',
        ]);
        $user = User::factory()->create(['sekolah_id' => $sekolahSaya->id]);

        Volt::actingAs($user)
            ->test('pages.pengaturan.sekolah')
            ->set('kode_sekolah', 'SDNLAIN')
            ->call('simpanProfil')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('sekolah', ['id' => $sekolahSaya->id, 'kode_sekolah' => 'SDNLAIN']);
    }

    public function test_pengaturan_sekolah_tolak_ganti_npsn_ke_yang_sudah_dipakai_orang_lain(): void
    {
        Sekolah::create([
            'nama_sekolah' => 'SDN Lain',
            'kode_sekolah' => 'SDNLAIN',
            'npsn' => '20601222',
            'nama_pemerintah' => 'X',
            'nama_dinas' => 'Y',
            'alamat' => 'Z',
            'tempat' => 'W',
        ]);
        $sekolahSaya = Sekolah::create([
            'nama_sekolah' => 'SDN Saya',
            'kode_sekolah' => 'SDNSAYA',
            'npsn' => '20601333',
            'nama_pemerintah' => 'X',
            'nama_dinas' => 'Y',
            'alamat' => 'Z',
            'tempat' => 'W',
        ]);
        $user = User::factory()->create(['sekolah_id' => $sekolahSaya->id]);

        Volt::actingAs($user)
            ->test('pages.pengaturan.sekolah')
            ->set('npsn', '20601222')
            ->call('simpanProfil')
            ->assertHasErrors(['npsn' => 'unique']);
    }

    public function test_topbar_menampilkan_daftar_tahun_anggaran_dan_bisa_pindah(): void
    {
        $sekolah = Sekolah::create([
            'nama_sekolah' => 'SDN 3 Rangkasbitung Timur',
            'kode_sekolah' => 'SDN3RKST',
            'nama_pemerintah' => 'X',
            'nama_dinas' => 'Y',
            'alamat' => 'Z',
            'tempat' => 'W',
        ]);
        $ta2026 = $sekolah->tahunAnggaran()->first();
        $ta2026->update(['status' => 'hold']);
        $ta2027 = TahunAnggaran::create(['sekolah_id' => $sekolah->id, 'tahun' => 2027, 'status' => 'aktif']);

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
            'nama_sekolah' => 'SDN Saya',
            'kode_sekolah' => 'SDNSAYA',
            'nama_pemerintah' => 'X',
            'nama_dinas' => 'Y',
            'alamat' => 'Z',
            'tempat' => 'W',
        ]);
        $sekolahLain = Sekolah::create([
            'nama_sekolah' => 'SDN Lain',
            'kode_sekolah' => 'SDNLAIN',
            'nama_pemerintah' => 'X',
            'nama_dinas' => 'Y',
            'alamat' => 'Z',
            'tempat' => 'W',
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
            'nama_sekolah' => 'SDN 3 Rangkasbitung Timur',
            'kode_sekolah' => 'SDN3RKST',
            'nama_pemerintah' => 'X',
            'nama_dinas' => 'Y',
            'alamat' => 'Z',
            'tempat' => 'W',
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
            'nama_sekolah' => 'SDN 3 Rangkasbitung Timur',
            'kode_sekolah' => 'SDN3RKST',
            'nama_pemerintah' => 'X',
            'nama_dinas' => 'Y',
            'alamat' => 'Z',
            'tempat' => 'W',
        ]);
        $user = User::factory()->create(['sekolah_id' => $sekolah->id]);

        Volt::actingAs($user)
            ->test('layout.topbar')
            ->call('logout');

        $this->assertGuest();
    }

    public function test_lengkapi_profil_kode_sekolah_selalu_kesimpen_uppercase(): void
    {
        $user = User::factory()->create(['sekolah_id' => null]);

        Volt::actingAs($user)
            ->test('pages.onboarding.lengkapi-profil')
            ->set('nama_sekolah', 'SDN Contoh')
            ->set('kode_sekolah', 'sdncontoh') // sengaja lowercase
            ->set('npsn', '20609999')
            ->set('nama_pemerintah', 'A')
            ->set('nama_dinas', 'B')
            ->set('alamat', 'C')
            ->set('tempat', 'D')
            ->call('simpan');

        $this->assertDatabaseHas('sekolah', ['nama_sekolah' => 'SDN Contoh', 'kode_sekolah' => 'SDNCONTOH']);
    }

    public function test_pengaturan_sekolah_kode_sekolah_selalu_kesimpen_uppercase(): void
    {
        $sekolah = Sekolah::create([
            'nama_sekolah' => 'SDN Test',
            'kode_sekolah' => 'SDNLAMA',
            'npsn' => '20609998',
            'nama_pemerintah' => 'X',
            'nama_dinas' => 'Y',
            'alamat' => 'Z',
            'tempat' => 'W',
        ]);
        $user = User::factory()->create(['sekolah_id' => $sekolah->id]);

        Volt::actingAs($user)
            ->test('pages.pengaturan.sekolah')
            ->set('kode_sekolah', 'sdnbaru') // sengaja lowercase
            ->call('simpanProfil')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('sekolah', ['id' => $sekolah->id, 'kode_sekolah' => 'SDNBARU']);
    }
}
