<?php

namespace Tests\Feature\Auth;

use App\Models\Sekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Livewire\Volt\Volt;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function fakeGoogleUser(string $id = '1234567890', string $email = 'budi@gmail.com', string $name = 'Budi Santoso'): void
    {
        $socialiteUser = (new SocialiteUser())->map([
            'id' => $id,
            'name' => $name,
            'nickname' => strtolower(str_replace(' ', '', $name)),
            'email' => $email,
            'avatar' => null,
        ]);

        Socialite::shouldReceive('driver->stateless->user')->andReturn($socialiteUser);
    }

    public function test_callback_bikin_user_baru_kalau_belum_pernah_login_google(): void
    {
        $this->fakeGoogleUser();

        $this->get(route('google.callback'))->assertRedirect(route('dashboard'));

        $user = User::where('email', 'budi@gmail.com')->firstOrFail();
        $this->assertSame('1234567890', $user->google_id);
        $this->assertSame('Budi Santoso', $user->name);
        $this->assertNotNull($user->email_verified_at); // langsung verified, nggak perlu link email
        $this->assertAuthenticatedAs($user);
    }

    public function test_callback_tautkan_ke_akun_manual_yang_udah_ada_kalau_email_sama(): void
    {
        $existing = User::factory()->unverified()->create(['email' => 'budi@gmail.com', 'google_id' => null]);

        $this->fakeGoogleUser(email: 'budi@gmail.com');

        $this->get(route('google.callback'))->assertRedirect(route('dashboard'));

        $existing->refresh();
        $this->assertSame('1234567890', $existing->google_id);
        $this->assertNotNull($existing->email_verified_at); // ikut ke-verifikasi otomatis
        $this->assertSame(1, User::where('email', 'budi@gmail.com')->count()); // nggak duplikat
    }

    public function test_callback_login_langsung_kalau_google_id_udah_pernah_match(): void
    {
        $user = User::factory()->create(['google_id' => '1234567890']);

        $this->fakeGoogleUser(id: '1234567890', email: $user->email);

        $this->get(route('google.callback'))->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, User::where('google_id', '1234567890')->count());
    }

    public function test_tombol_google_muncul_di_halaman_login(): void
    {
        Volt::test('pages.auth.login')->assertSeeHtml(route('google.redirect'));
    }

    public function test_tombol_google_muncul_di_halaman_register(): void
    {
        Volt::test('pages.auth.register')->assertSeeHtml(route('google.redirect'));
    }

    public function test_akun_google_tidak_bisa_ganti_password(): void
    {
        $sekolah = Sekolah::create([
            'nama_sekolah' => 'SDN Test',
            'kode_sekolah' => 'SDNTEST',
            'npsn' => '20601888',
            'nama_pemerintah' => 'X',
            'nama_dinas' => 'Y',
            'alamat' => 'Z',
            'tempat' => 'W',
        ]);
        $user = User::factory()->create(['sekolah_id' => $sekolah->id, 'google_id' => '1234567890']);

        Volt::actingAs($user)
            ->test('pages.pengaturan.sekolah')
            ->assertDontSeeHtml('wire:submit="gantiPassword"')
            ->call('gantiPassword')
            ->assertForbidden();
    }

    public function test_akun_biasa_tetap_bisa_ganti_password(): void
    {
        $sekolah = Sekolah::create([
            'nama_sekolah' => 'SDN Test 2',
            'kode_sekolah' => 'SDNTEST2',
            'npsn' => '20601889',
            'nama_pemerintah' => 'X',
            'nama_dinas' => 'Y',
            'alamat' => 'Z',
            'tempat' => 'W',
        ]);
        $user = User::factory()->create(['sekolah_id' => $sekolah->id, 'google_id' => null]);

        Volt::actingAs($user)
            ->test('pages.pengaturan.sekolah')
            ->assertSeeHtml('wire:submit="gantiPassword"')
            ->set('password_baru', 'password-baru-123')
            ->set('password_baru_confirmation', 'password-baru-123')
            ->call('gantiPassword')
            ->assertHasNoErrors();
    }
}
