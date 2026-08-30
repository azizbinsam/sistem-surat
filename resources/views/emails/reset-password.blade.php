@component('emails.layout', ['namaAplikasi' => $namaAplikasi])
    <h1 style="margin:0 0 16px; font-size:20px; font-weight:700; color:#18181b;">
        Reset Password
    </h1>

    <p style="margin:0 0 20px; font-size:14px; color:#52525b; line-height:1.6;">
        Halo {{ $namaPengguna }}, kami menerima permintaan buat reset password akun kamu di
        <strong>{{ $namaAplikasi }}</strong>. Klik tombol di bawah ini buat bikin password baru.
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 20px;">
        <tr>
            <td style="border-radius:8px; background-color:#059669;">
                <a href="{{ $resetUrl }}" target="_blank"
                    style="display:inline-block; padding:12px 28px; font-size:14px; font-weight:600; color:#ffffff; text-decoration:none;">
                    Reset Password
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 8px; font-size:13px; color:#a1a1aa; line-height:1.6;">
        Tombolnya nggak bisa diklik? Salin & buka link berikut di browser kamu:
    </p>
    <p style="margin:0; font-size:12px; color:#3f83f8; word-break:break-all; line-height:1.6;">
        {{ $resetUrl }}
    </p>

    <p style="margin:24px 0 0; font-size:12px; color:#a1a1aa; line-height:1.6;">
        Link ini berlaku selama 60 menit. Kalau kamu nggak merasa minta reset password, abaikan aja
        email ini — password kamu tetap aman, nggak ada yang berubah.
    </p>
@endcomponent
