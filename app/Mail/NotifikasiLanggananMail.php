<?php

namespace App\Mail;

use App\Models\AppSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Satu Mailable untuk semua email langganan (disetujui, ditolak, hampir kedaluwarsa, pengingat draft,
 * pengingat admin). Yang membedakan hanya subjek, view `emails.langganan.*`, dan data — tampilannya
 * tetap konsisten karena semua view memakai `emails.layout`.
 */
class NotifikasiLanggananMail extends Mailable
{
    use Queueable;

    public function __construct(
        public string $subjek,
        public string $tampilan,
        public array $data = [],
        public ?string $lampiranPdf = null,
        public ?string $namaLampiran = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjek);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.langganan.' . $this->tampilan,
            with: $this->data + ['namaAplikasi' => AppSettings::current()->nama_aplikasi],
        );
    }

    public function attachments(): array
    {
        if ($this->lampiranPdf === null) {
            return [];
        }

        return [
            Attachment::fromData(fn() => $this->lampiranPdf, $this->namaLampiran ?? 'Kwitansi.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
