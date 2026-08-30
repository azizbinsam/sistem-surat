<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordBase;
use Illuminate\Notifications\Messages\MailMessage;
use App\Mail\ResetPasswordMail;

/**
 * Sama polanya dengan VerifyEmailNotification — extend class bawaan Laravel
 * biar tetap dapat logic pembuatan URL reset-nya gratis, cuma ganti tampilan
 * emailnya jadi Mailable custom.
 */
class ResetPasswordNotification extends ResetPasswordBase
{
    public function toMail($notifiable): MailMessage|ResetPasswordMail
    {
        $url = $this->resetUrl($notifiable);

        return (new ResetPasswordMail($notifiable, $url))->to($notifiable->getEmailForPasswordReset());
    }
}
