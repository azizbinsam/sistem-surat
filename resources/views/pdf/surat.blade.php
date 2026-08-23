<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 60px 50px; }
        body { font-family: 'Times New Roman', serif; font-size: 12px; }
        .kop { text-align: center; margin-bottom: 20px; }
        .kop table { width: 100%; border: none; }
        .kop td { border: none; vertical-align: middle; }
        .kop img { max-height: 70px; }
        .kop .teks strong { font-size: 13px; }
        .judul { text-align: center; font-weight: bold; text-decoration: underline; margin: 10px 0; font-size: 13px; }
        .info-line { margin: 4px 0; }
        table.item { width: 100%; border-collapse: collapse; margin: 15px 0; }
        table.item th, table.item td { border: 1px solid #000; padding: 4px 6px; font-size: 11px; }
        table.item th { text-align: center; background: #f0f0f0; }
        .ttd-block { margin-top: 30px; width: 250px; float: right; text-align: center; }
        .ttd-space { height: 60px; }
        .ttd-space img { max-height: 55px; }
        .clearfix { clear: both; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>

@foreach (['npb', 'spb', 'sppb'] as $i => $jenis)
    @if ($i > 0) <div class="page-break"></div> @endif

    <div class="kop">
        <table>
            <tr>
                <td style="width: 15%; text-align: center;">
                    @if ($sekolah->logo_kabupaten)
                        <img src="{{ storage_path('app/public/'.$sekolah->logo_kabupaten) }}">
                    @endif
                </td>
                <td style="width: 70%;" class="teks">
                    <strong>{{ $sekolah->nama_pemerintah }}</strong><br>
                    {{ $sekolah->nama_dinas }}<br>
                    @if ($sekolah->nama_korwil) {{ $sekolah->nama_korwil }}<br> @endif
                    <strong>{{ $sekolah->nama_sekolah }}</strong><br>
                    <span style="font-size: 10px;">{{ $sekolah->alamat }}</span>
                </td>
                <td style="width: 15%; text-align: center;">
                    @if ($sekolah->logo_sekolah)
                        <img src="{{ storage_path('app/public/'.$sekolah->logo_sekolah) }}">
                    @endif
                </td>
            </tr>
        </table>
        <hr>
    </div>

    @if ($jenis === 'npb')
        <div class="judul">NOTA PERMINTAAN BARANG</div>
        <div class="info-line">Nomor : {{ $transaksi->nomor_npb }}</div>
        <div class="info-line">Pihak Yang meminta : {{ $peminta->jabatan }}</div>

        <table class="item">
            <thead>
                <tr><th>No</th><th>Spesifikasi Nama Barang</th><th>Jumlah</th><th>Satuan Barang</th><th>Keperluan</th><th>Ket.</th></tr>
            </thead>
            <tbody>
                @foreach ($items as $idx => $item)
                    <tr>
                        <td style="text-align:center;">{{ $idx + 1 }}</td>
                        <td>{{ $item->spesifikasi }}</td>
                        <td style="text-align:center;">{{ $item->jumlah }}</td>
                        <td style="text-align:center;">{{ $item->satuan }}</td>
                        <td>{{ $item->keperluan }}</td>
                        <td></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="ttd-block">
            {{ $sekolah->tempat }}, {{ $tanggalFormat['npb'] }}<br>
            {{ $peminta->jabatan }}
            <div class="ttd-space">
                @if ($peminta->ttd_path)
                    <img src="{{ storage_path('app/public/'.$peminta->ttd_path) }}">
                @endif
            </div>
            {{ $peminta->nama }}<br>
            NIP. {{ $peminta->nip ?? '-' }}
        </div>
        <div class="clearfix"></div>

    @elseif ($jenis === 'spb')
        <div class="judul">SURAT PERMINTAAN BARANG</div>
        <div class="info-line">Nomor : {{ $transaksi->nomor_spb }}</div>
        <div class="info-line">a.&nbsp;&nbsp;Nomor&nbsp;&nbsp;: {{ $transaksi->nomor_npb }}</div>
        <div class="info-line">b.&nbsp;&nbsp;Tanggal&nbsp;&nbsp;: {{ $tanggalFormat['npb'] }}</div>
        <div class="info-line">c.&nbsp;&nbsp;Pihak yang meminta&nbsp;&nbsp;: {{ $peminta->jabatan }}</div>

        <table class="item">
            <thead>
                <tr>
                    <th>No.</th><th>Kode Barang</th><th>Nama Barang</th><th>Spesifikasi</th>
                    <th>Pengajuan Permintaan</th><th>Informasi Sisa Persediaan</th><th>Usulan Pengajuan Persetujuan</th>
                    <th>Keperluan</th><th>Ket.</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $idx => $item)
                    <tr>
                        <td style="text-align:center;">{{ $idx + 1 }}</td>
                        <td>{{ $item->masterBarang->kode_barang }}</td>
                        <td>{{ $item->masterBarang->nama_barang }}</td>
                        <td>{{ $item->spesifikasi }}</td>
                        <td style="text-align:center;">{{ $item->jumlah }} {{ $item->satuan }}</td>
                        <td style="text-align:center;">{{ $sisaPerItem[$item->id] }} {{ $item->masterBarang->satuan_default }}</td>
                        <td style="text-align:center;">{{ $item->jumlah }} {{ $item->satuan }}</td>
                        <td>{{ $item->keperluan }}</td>
                        <td></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="ttd-block">
            {{ $sekolah->tempat }}, {{ $tanggalFormat['spb'] }}<br>
            {{ $pbp->jabatan ?? '-' }}
            <div class="ttd-space">
                @if ($pbp?->ttd_path)
                    <img src="{{ storage_path('app/public/'.$pbp->ttd_path) }}">
                @endif
            </div>
            {{ $pbp->nama ?? '-' }}<br>
            NIP. {{ $pbp->nip ?? '-' }}
        </div>
        <div class="clearfix"></div>

    @else
        <div class="judul">SURAT PERINTAH PENYALURAN BARANG</div>
        <div class="info-line">Nomor : {{ $transaksi->nomor_sppb }}</div>
        <div class="info-line">a.&nbsp;&nbsp;Nomor&nbsp;&nbsp;: {{ $transaksi->nomor_spb }}</div>
        <div class="info-line">b.&nbsp;&nbsp;Tanggal&nbsp;&nbsp;: {{ $tanggalFormat['spb'] }}</div>
        <div class="info-line">c.&nbsp;&nbsp;Pihak yang meminta&nbsp;&nbsp;: {{ $peminta->jabatan }}</div>

        <table class="item">
            <thead>
                <tr><th>No.</th><th>Kode Barang</th><th>Nama Barang</th><th>Spesifikasi</th><th>Persetujuan Pengeluaran</th><th>Ket.</th></tr>
            </thead>
            <tbody>
                @foreach ($items as $idx => $item)
                    <tr>
                        <td style="text-align:center;">{{ $idx + 1 }}</td>
                        <td>{{ $item->masterBarang->kode_barang }}</td>
                        <td>{{ $item->masterBarang->nama_barang }}</td>
                        <td>{{ $item->spesifikasi }}</td>
                        <td style="text-align:center;">{{ $item->jumlah }} {{ $item->satuan }}</td>
                        <td></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="ttd-block">
            {{ $sekolah->tempat }}, {{ $tanggalFormat['sppb'] }}<br>
            {{ $sekolah->jabatan_resmi_sppb }}
            <div class="ttd-space">
                @if ($kepsek?->ttd_path)
                    <img src="{{ storage_path('app/public/'.$kepsek->ttd_path) }}">
                @endif
            </div>
            {{ $kepsek->nama ?? '-' }}<br>
            NIP. {{ $kepsek->nip ?? '-' }}
        </div>
        <div class="clearfix"></div>
    @endif
@endforeach

</body>
</html>