<?php

namespace Tests\Feature\Auth;

use App\Mail\ResetPasswordMail;
use App\Mail\SelamatDatangGoogleMail;
use App\Mail\VerifikasiEmailMail;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TemplateEmailAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_verifikasi_email_pakai_mailable_custom(): void
    {
        // Notification::fake() dipakai di sini, BUKAN Mail::fake() — soalnya
        // notifikasi yang toMail()-nya balikin Mailable custom itu ngirim lewat
        // Mailable::send($mailer) versi low-level (buildView() dulu, baru dikirim),
        // bukan lewat Mail::to()->send($mailable) yang biasa. Mail::fake() nggak
        // reliable nangkep pola itu, jadi kita cek di level notifikasi aja.
        Notification::fake();

        $user = User::factory()->unverified()->create();
        $user->sendEmailVerificationNotification();

        Notification::assertSentTo($user, VerifyEmailNotification::class, function (VerifyEmailNotification $notification) use ($user) {
            $mail = $notification->toMail($user);

            return $mail instanceof VerifikasiEmailMail && $mail->hasTo($user->email) && $mail->user->is($user);
        });
    }

    public function test_reset_password_pakai_mailable_custom(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $user->sendPasswordResetNotification('contoh-token-123');

        Notification::assertSentTo($user, ResetPasswordNotification::class, function (ResetPasswordNotification $notification) use ($user) {
            $mail = $notification->toMail($user);

            return $mail instanceof ResetPasswordMail
                && $mail->hasTo($user->email)
                && str_contains($mail->resetUrl, 'contoh-token-123')
                && str_contains($mail->resetUrl, urlencode($user->email));
        });
    }

    public function test_isi_email_verifikasi_ada_nama_dan_link(): void
    {
        $user = new User(['name' => 'Budi Santoso', 'email' => 'budi@example.com']);
        $mail = new VerifikasiEmailMail($user, 'https://app.test/verify-email/1/hash');

        $mail->assertSeeInHtml('Budi Santoso');
        $mail->assertSeeInHtml('https://app.test/verify-email/1/hash');
    }

    public function test_isi_email_reset_password_ada_nama_dan_link(): void
    {
        $user = new User(['name' => 'Budi Santoso', 'email' => 'budi@example.com']);
        $mail = new ResetPasswordMail($user, 'https://app.test/reset-password/token123');

        $mail->assertSeeInHtml('Budi Santoso');
        $mail->assertSeeInHtml('https://app.test/reset-password/token123');
    }

    public function test_route_preview_verifikasi_bisa_diakses_di_testing(): void
    {
        $this->get(route('dev.preview-email.verifikasi'))
            ->assertOk()
            ->assertSeeText('Budi Santoso');
    }

    public function test_route_preview_reset_password_bisa_diakses_di_testing(): void
    {
        $this->get(route('dev.preview-email.reset-password'))
            ->assertOk()
            ->assertSeeText('Budi Santoso');
    }

    public function test_isi_email_selamat_datang_google_ada_nama_dan_tanpa_kata_verifikasi(): void
    {
        $user = new User(['name' => 'Budi Santoso', 'email' => 'budi@gmail.com']);
        $mail = new SelamatDatangGoogleMail($user);

        $mail->assertSeeInHtml('Budi Santoso');
        // Pastikan nggak ada ajakan "verifikasi email" nyasar ke template ini —
        // beda tujuan sama VerifikasiEmailMail.
        $mail->assertDontSeeInHtml('Verifikasi Email');
    }

    public function test_route_preview_selamat_datang_google_bisa_diakses_di_testing(): void
    {
        $this->get(route('dev.preview-email.selamat-datang-google'))
            ->assertOk()
            ->assertSeeText('Budi Santoso');
    }
}