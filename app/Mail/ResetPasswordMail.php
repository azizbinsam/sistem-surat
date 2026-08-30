<?php

namespace App\Mail;

use App\Models\AppSettings;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $resetUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reset Password — ' . AppSettings::current()->nama_aplikasi,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reset-password',
            with: [
                'namaPengguna' => $this->user->name,
                'resetUrl' => $this->resetUrl,
                'namaAplikasi' => AppSettings::current()->nama_aplikasi,
            ],
        );
    }
}
