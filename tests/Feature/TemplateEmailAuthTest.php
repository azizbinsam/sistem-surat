<?php

namespace Tests\Feature\Auth;

use App\Mail\ResetPasswordMail;
use App\Mail\VerifikasiEmailMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TemplateEmailAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_verifikasi_email_pakai_mailable_custom(): void
    {
        Mail::fake();

        $user = User::factory()->unverified()->create();
        $user->sendEmailVerificationNotification();

        Mail::assertSent(VerifikasiEmailMail::class, function (VerifikasiEmailMail $mail) use ($user) {
            return $mail->hasTo($user->email) && $mail->user->is($user);
        });
    }

    public function test_reset_password_pakai_mailable_custom(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $user->sendPasswordResetNotification('contoh-token-123');

        Mail::assertSent(ResetPasswordMail::class, function (ResetPasswordMail $mail) use ($user) {
            return $mail->hasTo($user->email)
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
}
