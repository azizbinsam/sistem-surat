@component('emails.layout', ['namaAplikasi' => $namaAplikasi])
    <h1 style="margin:0 0 16px; font-size:20px; font-weight:700; color:#18181b;">
        Selamat datang, {{ $namaPengguna }} 👋
    </h1>

    <p style="margin:0 0 20px; font-size:14px; color:#52525b; line-height:1.6;">
        Akun kamu di <strong>{{ $namaAplikasi }}</strong> berhasil dibuat. Sebelum mulai pakai semua fitur,
        yuk verifikasi dulu alamat email kamu dengan klik tombol di bawah ini.
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 20px;">
        <tr>
            <td style="border-radius:8px; background-color:#059669;">
                <a href="{{ $verificationUrl }}" target="_blank"
                    style="display:inline-block; padding:12px 28px; font-size:14px; font-weight:600; color:#ffffff; text-decoration:none;">
                    Verifikasi Email
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 8px; font-size:13px; color:#a1a1aa; line-height:1.6;">
        Tombolnya nggak bisa diklik? Salin & buka link berikut di browser kamu:
    </p>
    <p style="margin:0; font-size:12px; color:#3f83f8; word-break:break-all; line-height:1.6;">
        {{ $verificationUrl }}
    </p>

    <p style="margin:24px 0 0; font-size:12px; color:#a1a1aa; line-height:1.6;">
        Link ini berlaku selama 60 menit. Kamu tetap bisa login & pakai dashboard sekarang juga sambil
        verifikasi ini menyusul — nggak perlu ditunggu dulu.
    </p>
@endcomponent
