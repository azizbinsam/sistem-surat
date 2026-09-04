<?php

namespace Tests\Feature;

use App\Models\Sekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

/**
 * Test ini sengaja nggak menguji SEMUA 19 form yang punya validasi (nggak
 * praktis) — cukup nguji beberapa titik representatif merata dari auth
 * sampai dalam dashboard sekolah, buat mastiin lang/id/*.php beneran ke-load
 * dan validasi keluar bahasa Indonesia, bukan default Laravel yang Inggris.
 */
class ValidasiBahasaIndonesiaTest extends TestCase
{
    use RefreshDatabase;

    protected Sekolah $sekolah;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sekolah = Sekolah::create([
            'nama_sekolah' => 'SDN Test',
            'kode_sekolah' => 'SDNTEST',
            'npsn' => '20609000',
            'nama_pemerintah' => 'X',
            'nama_dinas' => 'Y',
            'alamat' => 'Z',
            'tempat' => 'W',
        ]);
        $this->user = User::factory()->create(['sekolah_id' => $this->sekolah->id]);
    }

    public function test_register_field_kosong_pesannya_bahasa_indonesia(): void
    {
        Volt::test('pages.auth.register')
            ->call('register')
            ->assertHasErrors(['name' => 'required'])
            ->assertSee('Nama wajib diisi')
            ->assertDontSee('The name field is required');
    }

    public function test_register_email_tidak_valid_pesannya_bahasa_indonesia(): void
    {
        Volt::test('pages.auth.register')
            ->set('name', 'Budi')
            ->set('email', 'bukan-email')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertSee('harus berupa alamat email yang valid')
            ->assertDontSee('must be a valid email address');
    }

    public function test_register_password_confirmation_tidak_cocok_pesannya_bahasa_indonesia(): void
    {
        Volt::test('pages.auth.register')
            ->set('name', 'Budi')
            ->set('email', 'budi@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'beda-sama-sekali')
            ->call('register')
            ->assertSee('Konfirmasi Password tidak cocok')
            ->assertDontSee('confirmation does not match');
    }

    public function test_login_gagal_pesannya_bahasa_indonesia(): void
    {
        Volt::test('pages.auth.login')
            ->set('form.email', $this->user->email)
            ->set('form.password', 'password-salah')
            ->call('login')
            ->assertHasErrors('form.email')
            ->assertSee('Email atau password yang dimasukkan salah')
            ->assertDontSee('These credentials do not match');
    }

    public function test_forgot_password_email_tidak_terdaftar_pesannya_bahasa_indonesia(): void
    {
        Volt::test('pages.auth.forgot-password')
            ->set('email', 'tidak-ada@example.com')
            ->call('sendPasswordResetLink')
            ->assertSee('Kami nggak nemu akun dengan alamat email tersebut')
            ->assertDontSee("can't find a user");
    }

    public function test_master_barang_field_kosong_pesannya_bahasa_indonesia(): void
    {
        Volt::actingAs($this->user)
            ->test('pages.master-barang.create')
            ->call('simpan')
            ->assertSee('Nama Barang wajib diisi')
            ->assertDontSee('The nama barang field is required');
    }

    public function test_pegawai_field_kosong_pesannya_bahasa_indonesia(): void
    {
        Volt::actingAs($this->user)
            ->test('pages.pegawai.create')
            ->call('simpan')
            ->assertSee('Nama wajib diisi')
            ->assertDontSee('field is required');
    }

    public function test_lengkapi_profil_field_kosong_pesannya_bahasa_indonesia(): void
    {
        $userTanpaSekolah = User::factory()->create(['sekolah_id' => null]);

        Volt::actingAs($userTanpaSekolah)
            ->test('pages.onboarding.lengkapi-profil')
            ->call('simpan')
            ->assertSee('Nama Sekolah wajib diisi')
            ->assertDontSee('field is required');
    }

    public function test_pengaturan_sekolah_field_kosong_pesannya_bahasa_indonesia(): void
    {
        Volt::actingAs($this->user)
            ->test('pages.pengaturan.sekolah')
            ->set('nama_sekolah', '')
            ->call('simpanProfil')
            ->assertSee('Nama Sekolah wajib diisi')
            ->assertDontSee('field is required');
    }

    public function test_transaksi_items_array_kosong_pesannya_bahasa_indonesia(): void
    {
        Volt::actingAs($this->user)
            ->test('pages.transaksi.create')
            ->set('nomor_referensi_asal', '')
            ->call('simpan')
            ->assertSee('Nomor Referensi Asal wajib diisi')
            ->assertDontSee('field is required');
    }
}