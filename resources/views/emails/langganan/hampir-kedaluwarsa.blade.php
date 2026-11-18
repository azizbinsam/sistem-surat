@component('emails.layout', ['namaAplikasi' => $namaAplikasi])
    <h1 style="margin:0 0 16px; font-size:20px; font-weight:700; color:#18181b;">
        Invoice kamu hampir kedaluwarsa ⏰
    </h1>

    <p style="margin:0 0 16px; font-size:14px; color:#52525b; line-height:1.6;">
        Invoice <strong>{{ $nomorInvoice }}</strong> ({{ $daftarTriwulan }}) belum dibayar dan akan kedaluwarsa pada
        <strong>{{ $jatuhTempo }}</strong>.
    </p>
    <p style="margin:0 0 16px; font-size:14px; color:#52525b; line-height:1.6;">
        Total yang harus ditransfer: <strong>Rp {{ $totalBayar }}</strong> (termasuk kode unik). Setelah transfer,
        upload bukti di halaman invoice.
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 20px;">
        <tr>
            <td style="border-radius:8px; background-color:#059669;">
                <a href="{{ $invoiceUrl }}" target="_blank"
                    style="display:inline-block; padding:12px 28px; font-size:14px; font-weight:600; color:#ffffff; text-decoration:none;">
                    Buka Invoice
                </a>
            </td>
        </tr>
    </table>
@endcomponent
