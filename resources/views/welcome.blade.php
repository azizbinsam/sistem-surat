<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ \App\Models\AppSettings::current()->nama_aplikasi }} — Generate Surat NPB/SPB/SPPB Otomatis</title>
    <meta name="description"
        content="Sistem untuk sekolah negeri: kelola persediaan barang dan generate surat NPB, SPB, SPPB otomatis dari data Excel, unduh dalam format Word. Harga per triwulan, coba gratis.">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased bg-white text-zinc-900">

    {{-- NAVBAR --}}
    <nav class="border-b border-zinc-100 sticky top-0 bg-white/80 backdrop-blur z-30">
        <div class="max-w-6xl mx-auto px-6 py-4 flex justify-between items-center">
            <x-app-logo class="font-bold text-lg" />
            <div class="hidden md:flex items-center gap-6 text-sm text-zinc-600">
                <a href="#fitur" class="hover:text-zinc-900">Fitur</a>
                @if ($harga)
                    <a href="#harga" class="hover:text-zinc-900">Harga</a>
                @endif
                <a href="#faq" class="hover:text-zinc-900">FAQ</a>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('login') }}" class="text-sm text-zinc-600 hover:text-zinc-900 px-3 py-2">Masuk</a>
                <a href="{{ route('register') }}"
                    class="text-sm bg-emerald-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-emerald-500 shadow-sm">
                    Daftar &amp; Coba Gratis
                </a>
            </div>
        </div>
    </nav>

    {{-- BANNER dari admin (menu Banner). Tidak tampil kalau tidak ada yang sedang tayang. --}}
    <div class="max-w-6xl mx-auto px-6 pt-4">
        <x-banner-slider lokasi="landing" />
    </div>

    {{-- HERO --}}
    <section class="relative overflow-hidden bg-zinc-900">
        <div class="max-w-4xl mx-auto px-6 pt-28 pb-24 text-center relative z-10">
            <span
                class="inline-block bg-emerald-600/20 text-emerald-400 text-xs font-semibold px-3 py-1 rounded-full mb-6">
                @if ($promo['tampil'] ?? false)
                    Gratis untuk sekolah yang mendaftar sampai {{ $promo['gratis_sampai'] }}
                @else
                    Coba gratis: 3 generate surat tanpa biaya
                @endif
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
                    Coba Gratis Sekarang
                </a>
                <a href="#fitur"
                    class="w-full sm:w-auto inline-block text-zinc-300 px-8 py-3.5 rounded-lg font-semibold border border-zinc-700 hover:bg-zinc-800 transition-colors">
                    Lihat Fitur
                </a>
            </div>
            <p class="text-xs text-zinc-500 mt-6">Sekolah baru dapat 3 generate surat gratis untuk mencoba. Tanpa kartu
                kredit.</p>
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

                {{-- 2. Generate nomor surat + unduh Word --}}
                <div class="bg-white p-6 rounded-2xl border border-zinc-100 shadow-sm">
                    <div class="w-10 h-10 bg-emerald-50 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <h3 class="font-semibold mb-2">Generate NPB, SPB & SPPB</h3>
                    <p class="text-sm text-zinc-500">Satu klik beri nomor tiga surat sekaligus, lalu unduh dalam format
                        Word — bisa satuan atau bulk.</p>
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
                    <h3 class="font-semibold mb-2">Harga Terjangkau, per Triwulan</h3>
                    <p class="text-sm text-zinc-500">Bayar hanya untuk triwulan yang kamu butuhkan. Input data, stok, dan
                        arsip selalu gratis dan tidak pernah dikunci.</p>
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
                <div class="grid sm:grid-cols-1 gap-8">
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

    {{-- HARGA (dibaca dari katalog di menu admin "Paket & Harga") --}}
    @if ($harga)
        <section id="harga" class="py-24 bg-zinc-50 scroll-mt-16">
            <div class="max-w-4xl mx-auto px-6">
                <div class="text-center mb-12">
                    <h2 class="text-2xl sm:text-3xl font-bold mb-3">Harga sederhana, per triwulan</h2>
                    <p class="text-zinc-500">Laporan persediaan dibuat tiap triwulan, jadi kamu bayar hanya saat
                        membutuhkannya.</p>
                </div>

                @if ($promo['tampil'] ?? false)
                    <div class="mb-10 p-5 rounded-2xl bg-emerald-50 border border-emerald-200 text-sm text-emerald-900">
                        <p class="font-semibold mb-1">🎉 Promo sekolah yang mendaftar sampai {{ $promo['gratis_sampai'] }}</p>
                        <p>Seluruh Tahun Anggaran 2026 <strong>gratis</strong>. Laporan TW4 2026 masih bisa dibuat sampai
                            {{ $promo['tw4_sampai'] }}. Paket berbayar baru berlaku mulai Tahun Anggaran 2027.</p>
                    </div>
                @endif

                <div class="grid sm:grid-cols-2 gap-6">
                    <div class="bg-white p-8 rounded-2xl border border-zinc-200 shadow-sm flex flex-col">
                        <h3 class="font-semibold text-lg mb-1">Triwulan</h3>
                        <p class="text-sm text-zinc-500 mb-5">Beli satu atau beberapa triwulan sesuai kebutuhan.</p>
                        <p class="mb-6"><span class="text-4xl font-bold">Rp{{ number_format($harga['triwulan'], 0, ',', '.') }}</span>
                            <span class="text-zinc-500 text-sm">/ triwulan</span></p>
                        <ul class="text-sm text-zinc-600 space-y-2 mb-8 grow">
                            <li>✓ Generate NPB, SPB &amp; SPPB untuk 1 triwulan</li>
                            <li>✓ Unduh Word tanpa batas</li>
                            <li>✓ Import Excel, ledger persediaan, dashboard</li>
                            <li>✓ Kwitansi pembayaran (PDF)</li>
                        </ul>
                        <a href="{{ route('register') }}"
                            class="block text-center border border-zinc-300 text-zinc-800 px-6 py-3 rounded-lg font-semibold hover:bg-zinc-50">Mulai
                            Coba Gratis</a>
                    </div>

                    <div class="bg-white p-8 rounded-2xl border-2 border-emerald-500 shadow-md flex flex-col relative">
                        @if ($harga['hemat'] > 0)
                            <span
                                class="absolute -top-3 right-6 bg-emerald-600 text-white text-xs font-semibold px-3 py-1 rounded-full">Hemat
                                Rp{{ number_format($harga['hemat'], 0, ',', '.') }}</span>
                        @endif
                        <h3 class="font-semibold text-lg mb-1">Tahunan</h3>
                        <p class="text-sm text-zinc-500 mb-5">Satu Tahun Anggaran penuh (4 triwulan sekaligus).</p>
                        <p class="mb-1"><span class="text-4xl font-bold">Rp{{ number_format($harga['tahunan'], 0, ',', '.') }}</span>
                            <span class="text-zinc-500 text-sm">/ tahun anggaran</span></p>
                        <p class="text-xs text-zinc-500 mb-6">≈ Rp{{ number_format($harga['per_triwulan_tahunan'], 0, ',', '.') }} per triwulan</p>
                        <ul class="text-sm text-zinc-600 space-y-2 mb-8 grow">
                            <li>✓ Generate surat untuk <strong>4 triwulan</strong></li>
                            <li>✓ Unduh Word tanpa batas</li>
                            <li>✓ Import Excel, ledger persediaan, dashboard</li>
                            <li>✓ Kwitansi pembayaran (PDF)</li>
                        </ul>
                        <a href="{{ route('register') }}"
                            class="block text-center bg-emerald-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-emerald-500 shadow-sm">Mulai
                            Coba Gratis</a>
                    </div>
                </div>

                <p class="text-center text-xs text-zinc-500 mt-8">Sekolah baru mendapat <strong>3 generate surat
                        gratis</strong> untuk mencoba. Input data, stok, dan arsip tetap gratis dan tidak pernah
                    dikunci.</p>
            </div>
        </section>

        {{-- CARA BERLANGGANAN --}}
        <section id="cara-berlangganan" class="py-24 scroll-mt-16">
            <div class="max-w-5xl mx-auto px-6">
                <div class="text-center mb-14">
                    <h2 class="text-2xl sm:text-3xl font-bold mb-3">Cara berlangganan</h2>
                    <p class="text-zinc-500">Empat langkah, pembayaran lewat transfer bank.</p>
                </div>
                @php
                    $langkahLangganan = [
                        ['Input data seperti biasa', 'Catat penerimaan dan pengeluaran barang. Bagian ini selalu gratis.'],
                        ['Pilih triwulan', 'Saat mau generate surat, buka menu Langganan, centang triwulan yang dibutuhkan, lalu buat invoice.'],
                        ['Transfer & upload bukti', 'Transfer sesuai nominal di invoice (ada kode unik di belakangnya), lalu unggah bukti transfer.'],
                        ['Aktif & kwitansi', 'Setelah diverifikasi admin, triwulan langsung aktif dan kwitansi PDF dikirim ke emailmu.'],
                    ];
                @endphp
                <ol class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach ($langkahLangganan as $i => $langkah)
                        <li class="bg-white p-6 rounded-2xl border border-zinc-100 shadow-sm">
                            <span
                                class="w-8 h-8 rounded-full bg-emerald-50 text-emerald-700 font-bold text-sm flex items-center justify-center mb-4">{{ $i + 1 }}</span>
                            <h3 class="font-semibold mb-2">{{ $langkah[0] }}</h3>
                            <p class="text-sm text-zinc-500">{{ $langkah[1] }}</p>
                        </li>
                    @endforeach
                </ol>
                <p class="text-center text-xs text-zinc-500 mt-8">Nomor rekening tujuan ditampilkan di halaman invoice
                    setelah kamu membuat pesanan, supaya setiap transfer bisa dicocokkan dengan invoice-nya.</p>
            </div>
        </section>
    @endif

    {{-- FAQ (pakai <details> bawaan HTML, tanpa JavaScript) --}}
    <section id="faq" class="py-24 bg-zinc-50 scroll-mt-16">
        <div class="max-w-3xl mx-auto px-6">
            <div class="text-center mb-12">
                <h2 class="text-2xl sm:text-3xl font-bold mb-3">Pertanyaan yang sering diajukan</h2>
            </div>
            @php
                $daftarFaq = [
                    ['Apa yang berbayar, apa yang gratis?', 'Yang berbayar hanya tombol Generate surat, dihitung per triwulan. Input data, perhitungan stok, dashboard, dan mengunduh surat yang sudah di-generate tidak dikunci.'],
                    ['Kalau saya tidak berlangganan, apakah data saya hilang?', 'Tidak. Data sekolah tidak pernah dikunci atau dihapus karena belum berlangganan. Kamu tetap bisa menginput dan melihat semuanya; yang tertahan hanya pembuatan nomor surat untuk triwulan yang belum dibeli.'],
                    ['Triwulan mana yang dihitung?', 'Triwulan dari tanggal transaksi, bukan kapan tombol Generate ditekan. Jadi laporan TW1 yang baru dibuat di bulan April atau Mei tetap bisa di-generate selama TW1 sudah dibeli.'],
                    ['Kenapa harganya per triwulan?', 'Karena sekolah membuat laporan persediaan tiap triwulan. Kamu hanya membayar untuk triwulan yang benar-benar dibuatkan suratnya.'],
                    ['Bisa membeli sebagian triwulan saja?', 'Bisa. Pilih satu atau beberapa triwulan. Kalau membeli semua sisa triwulan sekaligus, harganya otomatis tidak melebihi harga Tahunan.'],
                    ['Bagaimana cara membayar dan kapan aktif?', 'Transfer bank sesuai nominal invoice, lalu upload bukti. Admin memverifikasi (biasanya maksimal 1×24 jam kerja); setelah itu triwulan langsung aktif. Invoice berlaku 7 hari.'],
                    ['Apakah ada kwitansi?', 'Ada. Kwitansi PDF dikirim otomatis ke email setelah pembayaran diverifikasi, dan bisa diunduh lagi dari halaman invoice.'],
                ];
            @endphp
            <div class="space-y-3">
                @foreach ($daftarFaq as $faq)
                    <details class="group bg-white rounded-xl border border-zinc-200 px-5 py-4">
                        <summary class="cursor-pointer font-medium text-zinc-900 list-none flex justify-between items-center gap-4">
                            {{ $faq[0] }}
                            <span class="text-zinc-400 group-open:rotate-45 transition-transform text-xl leading-none">+</span>
                        </summary>
                        <p class="text-sm text-zinc-600 mt-3">{{ $faq[1] }}</p>
                    </details>
                @endforeach

                @if ($promo['tampil'] ?? false)
                    <details class="group bg-white rounded-xl border border-zinc-200 px-5 py-4">
                        <summary class="cursor-pointer font-medium text-zinc-900 list-none flex justify-between items-center gap-4">
                            Saya sudah terdaftar. Apakah tetap gratis?
                            <span class="text-zinc-400 group-open:rotate-45 transition-transform text-xl leading-none">+</span>
                        </summary>
                        <p class="text-sm text-zinc-600 mt-3">Ya. Untuk sekolah yang mendaftar sampai {{ $promo['gratis_sampai'] }},
                            seluruh Tahun Anggaran 2026 gratis. Laporan TW4 2026 masih bisa dibuat sampai
                            {{ $promo['tw4_sampai'] }}. Paket berbayar mulai berlaku untuk Tahun Anggaran 2027.</p>
                    </details>
                @endif
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="bg-zinc-900 py-20">
        <div class="max-w-2xl mx-auto px-6 text-center">
            <h2 class="text-2xl sm:text-3xl font-bold text-white mb-4">Siap hemat waktu bikin surat?</h2>
            <p class="text-zinc-400 mb-8">Daftar sekarang dan coba 3 generate surat gratis, tanpa kartu kredit.</p>
            <a href="{{ route('register') }}"
                class="inline-block bg-emerald-600 text-white px-8 py-3.5 rounded-lg font-semibold hover:bg-emerald-500 shadow-lg shadow-emerald-600/20">
                Daftar &amp; Coba Gratis
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
