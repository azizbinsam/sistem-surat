<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} — Generate Surat NPB/SPB/SPPB Otomatis</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased bg-white text-zinc-900">

    {{-- NAVBAR --}}
    <nav class="border-b border-zinc-100">
        <div class="max-w-6xl mx-auto px-6 py-4 flex justify-between items-center">
            <span class="font-bold text-lg">{{ config('app.name') }}</span>
            <div class="space-x-4">
                <a href="{{ route('login') }}" class="text-sm text-zinc-600 hover:text-zinc-900">Masuk</a>
                <a href="{{ route('register') }}"
                    class="text-sm bg-zinc-900 text-white px-4 py-2 rounded-md hover:bg-zinc-700">Daftar Gratis</a>
            </div>
        </div>
    </nav>

    {{-- HERO --}}
    <section class="max-w-4xl mx-auto px-6 py-24 text-center">
        <h1 class="text-4xl sm:text-5xl font-bold tracking-tight mb-6">
            Surat NPB, SPB, SPPB<br>Digenerate Otomatis dari Excel.
        </h1>
        <p class="text-lg text-zinc-600 mb-10 max-w-2xl mx-auto">
            Upload data barang masuk & permintaan sekali, sistem urus sisanya — hitung sisa persediaan, nomor surat,
            sampai jadi dokumen Word & PDF siap tanda tangan.
        </p>
        <a href="{{ route('register') }}"
            class="inline-block bg-zinc-900 text-white px-8 py-3 rounded-md font-medium hover:bg-zinc-700">
            Coba Sekarang
        </a>
    </section>

    {{-- FITUR --}}
    <section class="bg-zinc-50 py-20">
        <div class="max-w-5xl mx-auto px-6">
            <h2 class="text-2xl font-bold text-center mb-12">Semua yang sekolah butuh, dalam satu tempat</h2>
            <div class="grid sm:grid-cols-3 gap-8">
                <div class="bg-white p-6 rounded-lg border border-zinc-100">
                    <h3 class="font-semibold mb-2">Transpose Otomatis</h3>
                    <p class="text-sm text-zinc-600">Upload data mentah, sistem kelompokkan otomatis jadi transaksi siap
                        surat.</p>
                </div>
                <div class="bg-white p-6 rounded-lg border border-zinc-100">
                    <h3 class="font-semibold mb-2">Ledger Persediaan Real-Time</h3>
                    <p class="text-sm text-zinc-600">Sisa stok dihitung otomatis dari riwayat penerimaan & pengeluaran
                        barang.</p>
                </div>
                <div class="bg-white p-6 rounded-lg border border-zinc-100">
                    <h3 class="font-semibold mb-2">Generate Word & PDF</h3>
                    <p class="text-sm text-zinc-600">Satu klik, dapat 3 surat sekaligus — bisa satuan atau bulk
                        sekaligus banyak transaksi.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- HARGA --}}
    @if ($paketList->isNotEmpty())
        <section class="py-20">
            <div class="max-w-4xl mx-auto px-6">
                <h2 class="text-2xl font-bold text-center mb-12">Harga Berlangganan</h2>
                <div class="grid sm:grid-cols-{{ min($paketList->count(), 3) }} gap-6">
                    @foreach ($paketList as $paket)
                        <div class="border border-zinc-200 rounded-lg p-6 text-center">
                            <h3 class="font-semibold text-lg mb-2">{{ $paket->nama_paket }}</h3>
                            <p class="text-3xl font-bold mb-1">Rp{{ number_format($paket->harga, 0, ',', '.') }}</p>
                            <p class="text-sm text-zinc-500 mb-6">/ {{ $paket->durasi_hari }} hari</p>
                            <a href="{{ route('register') }}"
                                class="block bg-zinc-900 text-white py-2 rounded-md text-sm hover:bg-zinc-700">Pilih
                                Paket</a>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- FOOTER --}}
    <footer class="border-t border-zinc-100 py-8">
        <div class="max-w-6xl mx-auto px-6 text-center text-sm text-zinc-500">
            &copy; {{ date('Y') }} {{ config('app.name') }}. Dibuat oleh Delix Studio.
        </div>
    </footer>

</body>

</html>
