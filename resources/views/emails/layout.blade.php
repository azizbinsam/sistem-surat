<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $namaAplikasi }}</title>
</head>

<body
    style="margin:0; padding:0; background-color:#f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
        style="background-color:#f4f4f5; padding: 32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                    style="max-width:480px; background-color:#ffffff; border-radius:12px; overflow:hidden; border:1px solid #e4e4e7;">

                    {{-- Header --}}
                    <tr>
                        <td style="background-color:#059669; padding:24px 32px;">
                            <span style="color:#ffffff; font-size:18px; font-weight:700;">{{ $namaAplikasi }}</span>
                        </td>
                    </tr>

                    {{-- Konten --}}
                    <tr>
                        <td style="padding:32px;">
                            {{ $slot }}
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:20px 32px; background-color:#fafafa; border-top:1px solid #e4e4e7;">
                            <p style="margin:0; font-size:12px; color:#a1a1aa; line-height:1.5;">
                                Email ini dikirim otomatis oleh {{ $namaAplikasi }}. Kalau kamu nggak merasa
                                melakukan aksi ini, abaikan saja email ini.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
