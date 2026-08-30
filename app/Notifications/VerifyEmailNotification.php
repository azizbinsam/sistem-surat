<?php

namespace App\Notifications;

use App\Mail\VerifikasiEmailMail;
use Illuminate\Auth\Notifications\VerifyEmail as VerifyEmailBase;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Ganti notifikasi verifikasi bawaan Laravel (markdown polos) dengan Mailable
 * custom bertema app ini. Extend class bawaan biar tetap dapat gratis logic
 * pembuatan signed URL-nya (verificationUrl()) tanpa perlu nulis ulang.
 */
class VerifyEmailNotification extends VerifyEmailBase
{
    public function toMail($notifiable): MailMessage|VerifikasiEmailMail
    {
        $url = $this->verificationUrl($notifiable);

        return (new VerifikasiEmailMail($notifiable, $url))->to($notifiable->getEmailForVerification());
    }
}