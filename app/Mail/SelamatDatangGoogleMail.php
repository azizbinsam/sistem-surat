<?php

namespace App\Mail;

use App\Models\AppSettings;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SelamatDatangGoogleMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Selamat Datang di ' . AppSettings::current()->nama_aplikasi,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.selamat-datang-google',
            with: [
                'namaPengguna' => $this->user->name,
                'emailPengguna' => $this->user->email,
                'namaAplikasi' => AppSettings::current()->nama_aplikasi,
                'dashboardUrl' => route('dashboard'),
            ],
        );
    }
}
