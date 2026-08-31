<?php

namespace App\Notifications;

use App\Mail\SelamatDatangGoogleMail;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi pendaftaran buat user yang daftar via Google — SENGAJA nggak
 * extend VerifyEmail kayak VerifyEmailNotification, karena user Google nggak
 * perlu (dan nggak boleh diminta) verifikasi email lagi; Google udah
 * memverifikasi kepemilikan emailnya dari sononya (email_verified_at diisi
 * langsung pas akun dibuat, lihat GoogleAuthController).
 */
class SelamatDatangGoogleNotification extends Notification
{
    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): SelamatDatangGoogleMail
    {
        return (new SelamatDatangGoogleMail($notifiable))->to($notifiable->email);
    }
}
