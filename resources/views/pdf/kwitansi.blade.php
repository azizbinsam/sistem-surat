<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>{{ $nomorKwitansi }}</title>
    <style>
        @page { margin: 28px 34px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #18181b; }
        h1 { font-size: 20px; margin: 0; letter-spacing: 3px; }
        .judul { border-bottom: 2px solid #059669; padding-bottom: 8px; margin-bottom: 14px; }
        .nomor { font-size: 10px; color: #52525b; margin-top: 3px; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data td { padding: 4px 0; vertical-align: top; }
        table.data td.label { width: 27%; color: #52525b; }
        .terbilang { background: #f4f4f5; padding: 7px 10px; font-style: italic; margin: 8px 0; }
        table.rincian { width: 100%; border-collapse: collapse; margin: 8px 0; }
        table.rincian td { padding: 3px 0; border-bottom: 1px solid #e4e4e7; }
        table.rincian td.nominal { text-align: right; }
        .total { font-size: 15px; font-weight: bold; margin-top: 10px; }
        .ttd { margin-top: 18px; float: right; width: 200px; text-align: center; }
        .stempel { color: #059669; font-weight: bold; font-size: 13px; border: 2px solid #059669; padding: 3px 12px; display: inline-block; }
    </style>
</head>

<body>
    <div class="judul">
        <h1>KWITANSI</h1>
        <div class="nomor">No. {{ $nomorKwitansi }} &nbsp;·&nbsp; Invoice {{ $invoice->nomor }}</div>
    </div>

    <table class="data">
        <tr>
            <td class="label">Telah diterima dari</td>
            <td>: <strong>{{ $sekolah->nama_sekolah }}</strong>@if ($sekolah->npsn) (NPSN {{ $sekolah->npsn }})@endif</td>
        </tr>
        <tr>
            <td class="label">Untuk pembayaran</td>
            <td>: Langganan layanan Sistem Surat —
                {{ $invoice->items->map(fn($i) => 'Triwulan ' . $i->triwulan . ' TA ' . $i->tahunAnggaran->tahun)->implode(', ') }}
            </td>
        </tr>
    </table>

    <table class="rincian">
        @foreach ($invoice->items as $item)
            <tr>
                <td>Triwulan {{ $item->triwulan }} — Tahun Anggaran {{ $item->tahunAnggaran->tahun }}</td>
                <td class="nominal">Rp {{ number_format($item->harga, 0, ',', '.') }}</td>
            </tr>
        @endforeach
        @if ($invoice->diskon > 0)
            <tr>
                <td>Diskon paket</td>
                <td class="nominal">− Rp {{ number_format($invoice->diskon, 0, ',', '.') }}</td>
            </tr>
        @endif
        <tr>
            <td>Kode unik pembayaran</td>
            <td class="nominal">Rp {{ number_format($invoice->kode_unik, 0, ',', '.') }}</td>
        </tr>
    </table>

    <div class="total">Jumlah dibayar: Rp {{ number_format($invoice->total_bayar, 0, ',', '.') }}</div>
    <div class="terbilang">Terbilang: {{ \App\Support\Terbilang::rupiah($invoice->total_bayar) }}</div>

    <div class="ttd">
        <div>Diverifikasi pada {{ optional($invoice->diverifikasi_at)->format('d-m-Y') }}</div>
        <div class="stempel" style="margin-top: 8px;">LUNAS</div>
        <div style="margin-top: 8px;"><strong>{{ $penerbit['nama'] }}</strong><br>{{ $penerbit['situs'] }}</div>
    </div>
</body>

</html>
