@component('emails.layout', ['namaAplikasi' => $namaAplikasi])
    <h1 style="margin:0 0 16px; font-size:20px; font-weight:700; color:#18181b;">
        Bukti transfer belum bisa kami terima
    </h1>

    <p style="margin:0 0 16px; font-size:14px; color:#52525b; line-height:1.6;">
        Bukti transfer untuk invoice <strong>{{ $nomorInvoice }}</strong> belum bisa diverifikasi.
    </p>
    <p style="margin:0 0 16px; font-size:14px; color:#18181b; line-height:1.6; background-color:#fef2f2; border:1px solid #fecaca; border-radius:8px; padding:12px;">
        <strong>Alasan:</strong> {{ $alasan }}
    </p>
    <p style="margin:0 0 16px; font-size:14px; color:#52525b; line-height:1.6;">
        Kamu masih bisa upload ulang bukti yang benar sampai <strong>{{ $batasUpload }}</strong>.
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 20px;">
        <tr>
            <td style="border-radius:8px; background-color:#059669;">
                <a href="{{ $invoiceUrl }}" target="_blank"
                    style="display:inline-block; padding:12px 28px; font-size:14px; font-weight:600; color:#ffffff; text-decoration:none;">
                    Upload Ulang Bukti
                </a>
            </td>
        </tr>
    </table>
@endcomponent
