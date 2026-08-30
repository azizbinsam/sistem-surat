<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Volt\Volt;
use Tests\TestCase;

class VerifikasiEmailNonBlockingTest extends TestCase
{
    use RefreshDatabase;

    public function test_registrasi_ngirim_notifikasi_verifikasi_ke_email(): void
    {
        Notification::fake();

        Volt::test('pages.auth.register')
            ->set('name', 'Sekolah Baru')
            ->set('email', 'sekolahbaru@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register');

        $user = User::where('email', 'sekolahbaru@example.com')->firstOrFail();

        // Ini yang sebelumnya nggak pernah kejadian sama sekali karena listener-nya
        // belum ke-daftar (lihat AppServiceProvider::boot()).
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_user_belum_verifikasi_tetap_bisa_akses_dashboard(): void
    {
        $sekolah = \App\Models\Sekolah::create([
            'nama_sekolah' => 'SDN Test',
            'kode_sekolah' => 'SDNTEST',
            'npsn' => '20601999',
            'nama_pemerintah' => 'X',
            'nama_dinas' => 'Y',
            'alamat' => 'Z',
            'tempat' => 'W',
        ]);
        $user = User::factory()->unverified()->create(['sekolah_id' => $sekolah->id]);

        // assertOk() sudah cukup membuktikan tidak di-redirect ke halaman
        // verifikasi — kalau middleware('verified') masih terpasang & aktif,
        // ini bakal jadi 302 redirect, bukan 200.
        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_banner_verifikasi_muncul_buat_user_yang_belum_verifikasi(): void
    {
        $user = User::factory()->unverified()->create();

        Volt::actingAs($user)
            ->test('layout.verifikasi-email-banner')
            ->assertSee('Email kamu belum diverifikasi');
    }

    public function test_banner_verifikasi_tidak_muncul_buat_user_yang_sudah_verifikasi(): void
    {
        $user = User::factory()->create(); // default: email_verified_at = now()

        Volt::actingAs($user)
            ->test('layout.verifikasi-email-banner')
            ->assertDontSee('Email kamu belum diverifikasi');
    }

    public function test_klik_kirim_ulang_di_banner_ngirim_notifikasi_baru(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        Volt::actingAs($user)
            ->test('layout.verifikasi-email-banner')
            ->call('kirimUlang')
            ->assertSee('Link verifikasi baru sudah dikirim');

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_tutup_banner_kesimpen_di_session_nggak_muncul_lagi(): void
    {
        $user = User::factory()->unverified()->create();

        Volt::actingAs($user)
            ->test('layout.verifikasi-email-banner')
            ->call('tutup')
            ->assertDontSee('Email kamu belum diverifikasi');

        // Komponen baru (misal abis pindah halaman) tetap inget udah ditutup
        Volt::actingAs($user)
            ->test('layout.verifikasi-email-banner')
            ->assertDontSee('Email kamu belum diverifikasi');
    }

    public function test_lupa_password_ngirim_notifikasi_reset(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'user@example.com']);

        Volt::test('pages.auth.forgot-password')
            ->set('email', 'user@example.com')
            ->call('sendPasswordResetLink');

        Notification::assertSentTo($user, ResetPassword::class);
    }
}
