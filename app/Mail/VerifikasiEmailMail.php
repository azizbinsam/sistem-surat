<?php

namespace App\Mail;

use App\Models\AppSettings;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerifikasiEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $verificationUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verifikasi Email — ' . AppSettings::current()->nama_aplikasi,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.verifikasi-email',
            with: [
                'namaPengguna' => $this->user->name,
                'verificationUrl' => $this->verificationUrl,
                'namaAplikasi' => AppSettings::current()->nama_aplikasi,
            ],
        );
    }
}
