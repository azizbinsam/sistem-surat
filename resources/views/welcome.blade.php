<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ \App\Models\AppSettings::current()->nama_aplikasi }} — Generate Surat NPB/SPB/SPPB Otomatis</title>
    <meta name="description"
        content="Sistem gratis untuk sekolah negeri: kelola persediaan barang dan generate surat NPB, SPB, SPPB otomatis dari data Excel. Tanpa biaya, tanpa ribet.">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased bg-white text-zinc-900">

    {{-- NAVBAR --}}
    <nav class="border-b border-zinc-100 sticky top-0 bg-white/80 backdrop-blur z-30">
        <div class="max-w-6xl mx-auto px-6 py-4 flex justify-between items-center">
            <x-app-logo class="font-bold text-lg" />
            <div class="flex items-center gap-3">
                <a href="{{ route('login') }}" class="text-sm text-zinc-600 hover:text-zinc-900 px-3 py-2">Masuk</a>
                <a href="{{ route('register') }}"
                    class="text-sm bg-emerald-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-emerald-500 shadow-sm">
                    Daftar Gratis
                </a>
            </div>
        </div>
    </nav>

    {{-- HERO --}}
    <section class="relative overflow-hidden bg-zinc-900">
        <div class="max-w-4xl mx-auto px-6 pt-28 pb-24 text-center relative z-10">
            <span
                class="inline-block bg-emerald-600/20 text-emerald-400 text-xs font-semibold px-3 py-1 rounded-full mb-6">
                100% Gratis untuk Sekolah di Kab. Lebak
            </span>
            <h1 class="text-4xl sm:text-5xl font-bold tracking-tight mb-6 text-white">
                Urus Persediaan & Surat Sekolah,<br>Bukan Ketikan Berulang.
            </h1>
            <p class="text-lg text-zinc-400 mb-10 max-w-2xl mx-auto">
                Upload data barang masuk & permintaan dari Excel, sistem urus sisanya — hitung sisa persediaan,
                nomor surat, sampai jadi dokumen NPB, SPB, dan SPPB siap tanda tangan.
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                <a href="{{ route('register') }}"
                    class="w-full sm:w-auto inline-block bg-emerald-600 text-white px-8 py-3.5 rounded-lg font-semibold hover:bg-emerald-500 shadow-lg shadow-emerald-600/20">
                    Coba Sekarang, Gratis
                </a>
                <a href="#fitur"
                    class="w-full sm:w-auto inline-block text-zinc-300 px-8 py-3.5 rounded-lg font-semibold border border-zinc-700 hover:bg-zinc-800 transition-colors">
                    Lihat Fitur
                </a>
            </div>
            <p class="text-xs text-zinc-500 mt-6">Tanpa kartu kredit. Tanpa biaya berlangganan.</p>
        </div>
        {{-- subtle grid decoration --}}
        <div class="absolute inset-0 opacity-[0.03]"
            style="background-image: linear-gradient(#fff 1px, transparent 1px), linear-gradient(90deg, #fff 1px, transparent 1px); background-size: 40px 40px;">
        </div>
    </section>

    {{-- FITUR --}}
    <section id="fitur" class="py-24 bg-zinc-50 scroll-mt-16">
        <div class="max-w-5xl mx-auto px-6">
            <div class="text-center mb-16">
                <h2 class="text-2xl sm:text-3xl font-bold mb-3">Semua yang sekolah butuh, dalam satu tempat</h2>
                <p class="text-zinc-500">Nggak perlu isi surat manual lagi satu-satu.</p>
            </div>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                {{-- 1. Transpose Otomatis --}}
                <div class="bg-white p-6 rounded-2xl border border-zinc-100 shadow-sm">
                    <div class="w-10 h-10 bg-emerald-50 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 17V7m0 10l-4-4m4 4l4-4m2-6v10m0-10l4 4m-4-4l-4 4" />
                        </svg>
                    </div>
                    <h3 class="font-semibold mb-2">Import & Transpose Otomatis</h3>
                    <p class="text-sm text-zinc-500">Upload data mentah dari Excel, sistem kelompokkan otomatis jadi
                        transaksi siap surat.</p>
                </div>

                {{-- 2. Generate Word & PDF --}}
                <div class="bg-white p-6 rounded-2xl border border-zinc-100 shadow-sm">
                    <div class="w-10 h-10 bg-emerald-50 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <h3 class="font-semibold mb-2">Generate NPB, SPB & SPPB</h3>
                    <p class="text-sm text-zinc-500">Satu klik, dapat tiga surat sekaligus dalam format Word & PDF —
                        bisa satuan atau bulk.</p>
                </div>

                {{-- 3. Ledger Persediaan --}}
                <div class="bg-white p-6 rounded-2xl border border-zinc-100 shadow-sm">
                    <div class="w-10 h-10 bg-emerald-50 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm6 0V9a2 2 0 012-2h2a2 2 0 012 2v10a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <h3 class="font-semibold mb-2">Ledger Persediaan Real-Time</h3>
                    <p class="text-sm text-zinc-500">Sisa stok dihitung otomatis dari riwayat penerimaan & pengeluaran
                        barang, plus alert kalau menipis.</p>
                </div>

                {{-- 4. Dashboard & Laporan Visual --}}
                <div class="bg-white p-6 rounded-2xl border border-zinc-100 shadow-sm">
                    <div class="w-10 h-10 bg-emerald-50 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                        </svg>
                    </div>
                    <h3 class="font-semibold mb-2">Dashboard & Laporan Visual</h3>
                    <p class="text-sm text-zinc-500">Pantau tren barang masuk-keluar, barang paling sering diminta,
                        dan status surat dalam satu layar.</p>
                </div>

                {{-- 5. Tahun Anggaran --}}
                <div class="bg-white p-6 rounded-2xl border border-zinc-100 shadow-sm">
                    <div class="w-10 h-10 bg-emerald-50 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h3 class="font-semibold mb-2">Rapi per Tahun Anggaran</h3>
                    <p class="text-sm text-zinc-500">Data dan nomor surat otomatis terpisah tiap tahun anggaran —
                        nggak akan tercampur dengan tahun sebelumnya.</p>
                </div>

                {{-- 6. Gratis --}}
                <div class="bg-white p-6 rounded-2xl border border-zinc-100 shadow-sm">
                    <div class="w-10 h-10 bg-emerald-50 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                        </svg>
                    </div>
                    <h3 class="font-semibold mb-2">Gratis, Didukung Donasi</h3>
                    <p class="text-sm text-zinc-500">Semua fitur bisa dipakai tanpa biaya. Kalau terbantu, donasi
                        seikhlasnya sangat berarti buat pengembangan.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- VIDEO TUTORIAL --}}
    @if ($videoTutorial->isNotEmpty())
        <section id="tutorial" class="py-24 scroll-mt-16">
            <div class="max-w-5xl mx-auto px-6">
                <div class="text-center mb-16">
                    <span
                        class="inline-block bg-emerald-50 text-emerald-600 text-xs font-semibold px-3 py-1 rounded-full mb-4">
                        📺 Video Tutorial
                    </span>
                    <h2 class="text-2xl sm:text-3xl font-bold mb-3">Belajar Pakai Sistem Ini dari Video</h2>
                    <p class="text-zinc-500">Panduan langkah demi langkah, dari upload data sampai generate surat.</p>
                </div>
                <div class="grid sm:grid-cols-2 gap-8">
                    @foreach ($videoTutorial as $video)
                        <div class="bg-white rounded-2xl border border-zinc-100 shadow-sm overflow-hidden">
                            @if ($video->embed_url)
                                <div class="aspect-video">
                                    <iframe class="w-full h-full" src="{{ $video->embed_url }}"
                                        title="{{ $video->judul }}" frameborder="0"
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                        allowfullscreen></iframe>
                                </div>
                            @endif
                            <div class="p-5">
                                <h3 class="font-semibold mb-1">{{ $video->judul }}</h3>
                                @if ($video->deskripsi)
                                    <p class="text-sm text-zinc-500">{{ $video->deskripsi }}</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- DONASI --}}
    @if ($rekeningDonasi->isNotEmpty())
        <section class="py-24">
            <div class="max-w-3xl mx-auto px-6 text-center">
                <span class="inline-block bg-rose-50 text-rose-600 text-xs font-semibold px-3 py-1 rounded-full mb-4">
                    ❤️ Dukung Kami
                </span>
                <h2 class="text-2xl sm:text-3xl font-bold mb-3">Aplikasi ini gratis.</h2>
                <p class="text-zinc-500 mb-10 max-w-xl mx-auto">
                    Nggak ada biaya berlangganan untuk sekolah manapun. Kalau aplikasi ini membantu pekerjaan kamu,
                    donasi seikhlasnya sangat berarti buat menjaga aplikasi ini tetap berjalan dan berkembang.
                </p>
                <div class="grid sm:grid-cols-2 gap-4 text-left">
                    @foreach ($rekeningDonasi as $rekening)
                        <div class="border border-zinc-200 rounded-xl p-4 flex gap-3 items-center">
                            @if ($rekening->foto)
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($rekening->foto) }}"
                                    class="w-14 h-14 rounded-lg object-cover shrink-0">
                            @else
                                <div
                                    class="w-14 h-14 rounded-lg bg-emerald-50 flex items-center justify-center shrink-0">
                                    <span
                                        class="text-emerald-600 font-semibold text-sm">{{ substr($rekening->nama_bank, 0, 2) }}</span>
                                </div>
                            @endif
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-zinc-900">{{ $rekening->nama_bank }}</p>
                                <p class="text-sm text-zinc-700 font-mono">{{ $rekening->nomor_rekening }}</p>
                                <p class="text-xs text-zinc-500">a.n. {{ $rekening->atas_nama }}</p>
                            </div>
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
            <p class="text-zinc-400 mb-8">Daftar sekarang, gratis, tanpa perlu kartu kredit.</p>
            <a href="{{ route('register') }}"
                class="inline-block bg-emerald-600 text-white px-8 py-3.5 rounded-lg font-semibold hover:bg-emerald-500 shadow-lg shadow-emerald-600/20">
                Daftar Gratis Sekarang
            </a>
        </div>
    </section>

    {{-- FOOTER --}}
    <footer class="py-8 bg-zinc-900 border-t border-zinc-800">
        <div class="max-w-6xl mx-auto px-6 text-center text-sm text-zinc-500">
            &copy; {{ date('Y') }} {{ \App\Models\AppSettings::current()->nama_aplikasi }}. Dibuat oleh Abdul Aziz
            (Delix
            Studio).
        </div>
    </footer>

</body>

</html>