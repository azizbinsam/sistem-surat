@component('emails.layout', ['namaAplikasi' => $namaAplikasi])
    <h1 style="margin:0 0 16px; font-size:20px; font-weight:700; color:#18181b;">
        Selamat datang, {{ $namaPengguna }} 👋
    </h1>

    <p style="margin:0 0 20px; font-size:14px; color:#52525b; line-height:1.6;">
        Akun kamu di <strong>{{ $namaAplikasi }}</strong> berhasil dibuat pakai Google
        (<strong>{{ $emailPengguna }}</strong>). Karena Google udah memverifikasi email kamu, kamu
        <strong>nggak perlu verifikasi email lagi</strong> — akun kamu langsung aktif dan siap dipakai.
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 20px;">
        <tr>
            <td style="border-radius:8px; background-color:#059669;">
                <a href="{{ $dashboardUrl }}" target="_blank"
                    style="display:inline-block; padding:12px 28px; font-size:14px; font-weight:600; color:#ffffff; text-decoration:none;">
                    Buka Dashboard
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:0; font-size:12px; color:#a1a1aa; line-height:1.6;">
        Login berikutnya cukup klik "Masuk dengan Google" — nggak ada password terpisah yang perlu kamu
        inget buat akun ini.
    </p>
@endcomponent
