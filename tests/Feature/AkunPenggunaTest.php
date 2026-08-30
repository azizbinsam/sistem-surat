<?php

namespace Tests\Feature;

use App\Models\Sekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Volt;
use Tests\TestCase;

/**
 * Sebelumnya nama/email login & ganti password dikelola lewat halaman /profile
 * bawaan Breeze (profile.update-profile-information-form, profile.update-password-form).
 * Halaman itu sudah dihapus karena orphan (nggak ke-link dari navigasi manapun) —
 * fungsinya sekarang digabung ke pages.pengaturan.sekolah (section "Akun Saya" &
 * "Keamanan"), biar semua pengaturan ada di satu tempat yang bisa dijangkau user.
 */
class AkunPenggunaTest extends TestCase
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
            'npsn' => '20601936',
            'nama_pemerintah' => 'PEMKAB LEBAK',
            'nama_dinas' => 'DISDIK',
            'alamat' => 'Jl. Contoh',
            'tempat' => 'Rangkasbitung',
        ]);

        $this->user = User::factory()->create([
            'sekolah_id' => $this->sekolah->id,
            'name' => 'Nama Lama',
            'email' => 'lama@example.com',
        ]);
    }

    public function test_akun_saya_terisi_otomatis_dari_user_yang_login(): void
    {
        Volt::actingAs($this->user)
            ->test('pages.pengaturan.sekolah')
            ->assertSet('nama_pengguna', 'Nama Lama')
            ->assertSet('email_pengguna', 'lama@example.com');
    }

    public function test_bisa_update_nama_dan_email_akun(): void
    {
        Volt::actingAs($this->user)
            ->test('pages.pengaturan.sekolah')
            ->set('nama_pengguna', 'Nama Baru')
            ->set('email_pengguna', 'baru@example.com')
            ->call('simpanAkun')
            ->assertHasNoErrors();

        $this->user->refresh();
        $this->assertSame('Nama Baru', $this->user->name);
        $this->assertSame('baru@example.com', $this->user->email);
    }

    public function test_tolak_email_akun_yang_sudah_dipakai_user_lain(): void
    {
        User::factory()->create(['email' => 'dipakai@example.com']);

        Volt::actingAs($this->user)
            ->test('pages.pengaturan.sekolah')
            ->set('email_pengguna', 'dipakai@example.com')
            ->call('simpanAkun')
            ->assertHasErrors(['email_pengguna' => 'unique']);
    }

    public function test_boleh_simpan_akun_tanpa_ubah_email_sendiri(): void
    {
        Volt::actingAs($this->user)
            ->test('pages.pengaturan.sekolah')
            ->set('nama_pengguna', 'Nama Lama Diedit Dikit')
            ->call('simpanAkun')
            ->assertHasNoErrors();
    }

    public function test_password_bisa_diubah_lewat_pengaturan_sekolah(): void
    {
        Volt::actingAs($this->user)
            ->test('pages.pengaturan.sekolah')
            ->set('password_baru', 'password-baru-123')
            ->set('password_baru_confirmation', 'password-baru-123')
            ->call('gantiPassword')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('password-baru-123', $this->user->refresh()->password));
    }

    public function test_ganti_password_tolak_kalau_konfirmasi_tidak_cocok(): void
    {
        Volt::actingAs($this->user)
            ->test('pages.pengaturan.sekolah')
            ->set('password_baru', 'password-baru-123')
            ->set('password_baru_confirmation', 'beda-sama-sekali')
            ->call('gantiPassword')
            ->assertHasErrors(['password_baru']);
    }
}