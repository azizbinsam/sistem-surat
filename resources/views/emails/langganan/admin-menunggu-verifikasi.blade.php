@component('emails.layout', ['namaAplikasi' => $namaAplikasi])
    <h1 style="margin:0 0 16px; font-size:20px; font-weight:700; color:#18181b;">
        Ada invoice menunggu verifikasi
    </h1>

    <p style="margin:0 0 16px; font-size:14px; color:#52525b; line-height:1.6;">
        <strong>{{ count($daftar) }} invoice</strong> sudah memiliki bukti transfer lebih dari 24 jam dan belum
        diverifikasi:
    </p>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 20px; font-size:13px; color:#3f3f46;">
        @foreach ($daftar as $baris)
            <tr>
                <td style="padding:6px 0; border-bottom:1px solid #e4e4e7;">{{ $baris['nomor'] }} — {{ $baris['sekolah'] }}</td>
                <td style="padding:6px 0; border-bottom:1px solid #e4e4e7;" align="right">Rp {{ $baris['total'] }} · {{ $baris['umur'] }}</td>
            </tr>
        @endforeach
    </table>

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 20px;">
        <tr>
            <td style="border-radius:8px; background-color:#059669;">
                <a href="{{ $adminUrl }}" target="_blank"
                    style="display:inline-block; padding:12px 28px; font-size:14px; font-weight:600; color:#ffffff; text-decoration:none;">
                    Buka Daftar Invoice
                </a>
            </td>
        </tr>
    </table>
@endcomponent
