@component('emails.layout', ['namaAplikasi' => $namaAplikasi])
    <h1 style="margin:0 0 16px; font-size:20px; font-weight:700; color:#18181b;">
        Ada surat yang menunggu di-generate
    </h1>

    <p style="margin:0 0 16px; font-size:14px; color:#52525b; line-height:1.6;">
        Kamu punya <strong>{{ $jumlahDraft }} transaksi draft</strong> di <strong>{{ $namaTriwulan }}</strong> yang
        belum bisa di-generate suratnya karena triwulan tersebut belum dibeli.
    </p>
    <p style="margin:0 0 16px; font-size:14px; color:#52525b; line-height:1.6;">
        Beli paket triwulan itu supaya laporan bisa langsung dibuat. Data kamu tetap aman — transaksi draft tidak hilang.
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 20px;">
        <tr>
            <td style="border-radius:8px; background-color:#059669;">
                <a href="{{ $langgananUrl }}" target="_blank"
                    style="display:inline-block; padding:12px 28px; font-size:14px; font-weight:600; color:#ffffff; text-decoration:none;">
                    Buka Halaman Langganan
                </a>
            </td>
        </tr>
    </table>
@endcomponent
