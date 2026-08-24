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
    <nav class="border-b border-zinc-100 sticky top-0 bg-white/80 backdrop-blur z-30">
        <div class="max-w-6xl mx-auto px-6 py-4 flex justify-between items-center">
            <span class="font-bold text-lg">{{ config('app.name') }}</span>
            <div class="flex items-center gap-3">
                <a href="{{ route('login') }}" class="text-sm text-zinc-600 hover:text-zinc-900 px-3 py-2">Masuk</a>
                <a href="{{ route('register') }}" class="text-sm bg-emerald-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-emerald-500 shadow-sm">Daftar Gratis</a>
            </div>
        </div>
    </nav>

    {{-- HERO --}}
    <section class="relative overflow-hidden bg-zinc-900">
        <div class="max-w-4xl mx-auto px-6 py-28 text-center relative z-10">
            <span class="inline-block bg-emerald-600/20 text-emerald-400 text-xs font-semibold px-3 py-1 rounded-full mb-6">
                Khusus Sekolah Negeri
            </span>
            <h1 class="text-4xl sm:text-5xl font-bold tracking-tight mb-6 text-white">
                Surat NPB, SPB, SPPB<br>Digenerate Otomatis dari Excel.
            </h1>
            <p class="text-lg text-zinc-400 mb-10 max-w-2xl mx-auto">
                Upload data barang masuk & permintaan sekali, sistem urus sisanya — hitung sisa persediaan, nomor surat, sampai jadi dokumen Word & PDF siap tanda tangan.
            </p>
            <a href="{{ route('register') }}" class="inline-block bg-emerald-600 text-white px-8 py-3.5 rounded-lg font-semibold hover:bg-emerald-500 shadow-lg shadow-emerald-600/20">
                Coba Sekarang, Gratis
            </a>
        </div>
        {{-- subtle grid decoration --}}
        <div class="absolute inset-0 opacity-[0.03]" style="background-image: linear-gradient(#fff 1px, transparent 1px), linear-gradient(90deg, #fff 1px, transparent 1px); background-size: 40px 40px;"></div>
    </section>

    {{-- FITUR --}}
    <section class="py-24 bg-zinc-50">
        <div class="max-w-5xl mx-auto px-6">
            <div class="text-center mb-16">
                <h2 class="text-2xl sm:text-3xl font-bold mb-3">Semua yang sekolah butuh, dalam satu tempat</h2>
                <p class="text-zinc-500">Nggak perlu isi surat manual lagi satu-satu.</p>
            </div>
            <div class="grid sm:grid-cols-3 gap-6">
                <div class="bg-white p-6 rounded-2xl border border-zinc-100 shadow-sm">
                    <div class="w-10 h-10 bg-emerald-50 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10l-4-4m4 4l4-4m2-6v10m0-10l4 4m-4-4l-4 4" /></svg>
                    </div>
                    <h3 class="font-semibold mb-2">Transpose Otomatis</h3>
                    <p class="text-sm text-zinc-500">Upload data mentah, sistem kelompokkan otomatis jadi transaksi siap surat.</p>
                </div>
                <div class="bg-white p-6 rounded-2xl border border-zinc-100 shadow-sm">
                    <div class="w-10 h-10 bg-emerald-50 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm6 0V9a2 2 0 012-2h2a2 2 0 012 2v10a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                    </div>
                    <h3 class="font-semibold mb-2">Ledger Persediaan Real-Time</h3>
                    <p class="text-sm text-zinc-500">Sisa stok dihitung otomatis dari riwayat penerimaan & pengeluaran barang.</p>
                </div>
                <div class="bg-white p-6 rounded-2xl border border-zinc-100 shadow-sm">
                    <div class="w-10 h-10 bg-emerald-50 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    </div>
                    <h3 class="font-semibold mb-2">Generate Word & PDF</h3>
                    <p class="text-sm text-zinc-500">Satu klik, dapat 3 surat sekaligus — bisa satuan atau bulk sekaligus banyak transaksi.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- HARGA --}}
    @if ($paketList->isNotEmpty())
        <section class="py-24">
            <div class="max-w-4xl mx-auto px-6">
                <div class="text-center mb-16">
                    <h2 class="text-2xl sm:text-3xl font-bold mb-3">Harga Berlangganan</h2>
                    <p class="text-zinc-500">Pilih paket yang sesuai kebutuhan sekolah kamu.</p>
                </div>
                <div class="grid sm:grid-cols-{{ min($paketList->count(), 3) }} gap-6">
                    @foreach ($paketList as $paket)
                        <div class="border border-zinc-200 rounded-2xl p-8 text-center hover:border-emerald-300 hover:shadow-lg transition">
                            <h3 class="font-semibold text-lg mb-2">{{ $paket->nama_paket }}</h3>
                            <p class="text-4xl font-bold mb-1">Rp{{ number_format($paket->harga, 0, ',', '.') }}</p>
                            <p class="text-sm text-zinc-500 mb-8">/ {{ $paket->durasi_hari }} hari</p>
                            <a href="{{ route('register') }}" class="block bg-zinc-900 text-white py-3 rounded-lg text-sm font-medium hover:bg-emerald-600 transition">Pilih Paket</a>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- CTA --}}
    <section class="bg-zinc-900 py-20">
        <div class="max-w-2xl mx-auto px-6 text-center">
            <h2 class="text-2xl sm:text-3xl font-bold text-white mb-4">Siap hemat waktu bikin surat?</h2>
            <p class="text-zinc-400 mb-8">Coba sekarang, tanpa perlu kartu kredit.</p>
            <a href="{{ route('register') }}" class="inline-block bg-emerald-600 text-white px-8 py-3.5 rounded-lg font-semibold hover:bg-emerald-500 shadow-lg shadow-emerald-600/20">
                Daftar Gratis Sekarang
            </a>
        </div>
    </section>

    {{-- FOOTER --}}
    <footer class="py-8 bg-zinc-900 border-t border-zinc-800">
        <div class="max-w-6xl mx-auto px-6 text-center text-sm text-zinc-500">
            &copy; {{ date('Y') }} {{ config('app.name') }}. Dibuat oleh Delix Studio.
        </div>
    </footer>

</body>
</html>