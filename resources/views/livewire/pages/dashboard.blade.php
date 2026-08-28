<?php

use App\Models\BarangMasuk;
use App\Models\MasterBarang;
use App\Models\Pegawai;
use App\Models\Transaksi;
use App\Models\TransaksiItem;
use App\Services\PersediaanService;
use App\Services\TahunAnggaranResolver;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    /** Ambang batas sederhana buat nandain stok "menipis" — belum ada pengaturan per barang, jadi angka tetap dulu. */
    const AMBANG_STOK_MENIPIS = 5;

    public function with(PersediaanService $persediaan, TahunAnggaranResolver $resolver): array
    {
        $sekolahId = auth()->user()->sekolah_id;
        $sekolah = auth()->user()->sekolah;

        // User yang belum lengkapi profil (harusnya keredirect middleware, tapi jaga-jaga
        // biar nggak crash kalau kepanggil sebelum itu) -> tampilkan dashboard kosong aja.
        if (!$sekolah) {
            return [
                'jumlahBarang' => 0,
                'jumlahPegawai' => 0,
                'draftMenunggu' => 0,
                'suratBulanIni' => 0,
                'tahunAnggaranAktif' => null,
                'labelBulan' => collect(),
                'masukPerBulan' => array_fill(0, 12, 0),
                'keluarPerBulan' => array_fill(0, 12, 0),
                'topBarang' => collect(),
                'draftVsSelesai' => [0, 0],
                'stokMenipis' => collect(),
                'rekeningDonasi' => collect(),
            ];
        }

        $tahunAnggaran = $resolver->aktif($sekolah);

        // ===== Kartu ringkasan =====
        $jumlahBarang = MasterBarang::where('sekolah_id', $sekolahId)->count();
        $jumlahPegawai = Pegawai::where('sekolah_id', $sekolahId)->count();
        $draftMenunggu = Transaksi::where('sekolah_id', $sekolahId)->where('status', 'draft')->count();
        $selesaiTotal = Transaksi::where('sekolah_id', $sekolahId)->where('status', 'selesai')->count();
        $suratBulanIni = Transaksi::where('sekolah_id', $sekolahId)->where('status', 'selesai')->whereMonth('updated_at', now()->month)->whereYear('updated_at', now()->year)->count();

        // ===== Chart 1: tren Barang Masuk vs Transaksi Keluar per bulan (tahun anggaran aktif) =====
        // Query lewat Eloquent (bukan raw MONTH()/strftime() biar portable MySQL & SQLite di test),
        // otomatis ke-scope ke tahun anggaran aktif lewat BelongsToTahunAnggaran (Fase 15).
        $masukPerBulan = array_fill(1, 12, 0);
        foreach (BarangMasuk::where('sekolah_id', $sekolahId)->pluck('tanggal') as $tanggal) {
            $masukPerBulan[$tanggal->month]++;
        }

        $keluarPerBulan = array_fill(1, 12, 0);
        foreach (Transaksi::where('sekolah_id', $sekolahId)->pluck('tanggal_npb') as $tanggal) {
            $keluarPerBulan[$tanggal->month]++;
        }

        $labelBulan = collect(range(1, 12))->map(fn($b) => Carbon::create()->month($b)->translatedFormat('M'))->values();

        // ===== Chart 2: top 5 barang paling sering keluar =====
        // whereHas otomatis ikut ke-filter tahun anggaran aktif juga, karena scope-nya nempel
        // di model Transaksi (TransaksiItem sendiri nggak punya kolom tahun_anggaran_id).
        $topBarang = TransaksiItem::whereHas('transaksi', fn($q) => $q->where('sekolah_id', $sekolahId))->selectRaw('master_barang_id, SUM(jumlah) as total_keluar')->groupBy('master_barang_id')->orderByDesc('total_keluar')->with('masterBarang')->take(5)->get()->filter(fn($row) => $row->masterBarang !== null);

        // ===== Alert: barang dengan sisa stok menipis =====
        $stokMenipis = MasterBarang::where('sekolah_id', $sekolahId)->get()->map(fn($barang) => ['barang' => $barang, 'sisa' => $persediaan->sisaSaatIni($barang->id)])->filter(fn($row) => $row['sisa'] <= self::AMBANG_STOK_MENIPIS)->sortBy('sisa')->values();

        // ===== Info donasi (tabelnya baru dibuat di Fase 20 — cek dulu biar nggak error) =====
        $rekeningDonasi = collect();
        if (\Illuminate\Support\Facades\Schema::hasTable('rekening_donasi')) {
            $rekeningDonasi = \App\Models\RekeningDonasi::orderBy('urutan')->get();
        }

        return [
            'jumlahBarang' => $jumlahBarang,
            'jumlahPegawai' => $jumlahPegawai,
            'draftMenunggu' => $draftMenunggu,
            'suratBulanIni' => $suratBulanIni,
            'tahunAnggaranAktif' => $tahunAnggaran,
            'labelBulan' => $labelBulan,
            'masukPerBulan' => array_values($masukPerBulan),
            'keluarPerBulan' => array_values($keluarPerBulan),
            'topBarang' => $topBarang,
            'draftVsSelesai' => [$draftMenunggu, $selesaiTotal],
            'stokMenipis' => $stokMenipis,
            'rekeningDonasi' => $rekeningDonasi,
        ];
    }
}; ?>

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-zinc-900">Selamat datang, {{ auth()->user()->name }} 👋</h1>
        <p class="text-zinc-500 text-sm mt-1">
            Ringkasan aktivitas sekolah kamu
            @if ($tahunAnggaranAktif)
                — Tahun Anggaran {{ $tahunAnggaranAktif->tahun }}
            @endif
        </p>
    </div>

    {{-- Kartu ringkasan --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-zinc-100 shadow-sm p-5">
            <p class="text-sm text-zinc-500">Master Barang</p>
            <p class="text-3xl font-bold text-zinc-900 mt-1">{{ $jumlahBarang }}</p>
        </div>
        <div class="bg-white rounded-xl border border-zinc-100 shadow-sm p-5">
            <p class="text-sm text-zinc-500">Pegawai Terdaftar</p>
            <p class="text-3xl font-bold text-zinc-900 mt-1">{{ $jumlahPegawai }}</p>
        </div>
        <div class="bg-white rounded-xl border border-zinc-100 shadow-sm p-5">
            <p class="text-sm text-zinc-500">Draft Menunggu</p>
            <p class="text-3xl font-bold text-zinc-900 mt-1">{{ $draftMenunggu }}</p>
            @if ($draftMenunggu > 0)
                <a href="{{ route('transaksi.index') }}" wire:navigate
                    class="text-xs text-emerald-600 hover:underline mt-1 inline-block">Proses sekarang →</a>
            @endif
        </div>
        <div class="bg-emerald-600 rounded-xl shadow-sm p-5">
            <p class="text-sm text-emerald-100">Surat Selesai Bulan Ini</p>
            <p class="text-3xl font-bold text-white mt-1">{{ $suratBulanIni }}</p>
        </div>
    </div>

    {{-- Alert stok menipis --}}
    @if ($stokMenipis->isNotEmpty())
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-5">
            <h2 class="font-semibold text-amber-900 flex items-center gap-2">
                ⚠ Stok Menipis ({{ $stokMenipis->count() }} barang)
            </h2>
            <p class="text-xs text-amber-700 mt-0.5 mb-3">Sisa {{ self::AMBANG_STOK_MENIPIS }} atau kurang.</p>
            <div class="flex flex-wrap gap-2">
                @foreach ($stokMenipis as $row)
                    <a href="{{ route('barang-masuk.create') }}" wire:navigate
                        class="text-xs bg-white border border-amber-200 text-amber-800 px-3 py-1.5 rounded-lg hover:bg-amber-100">
                        {{ $row['barang']->nama_barang }} — sisa <strong>{{ $row['sisa'] }}</strong>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Chart tren & top barang --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 bg-white rounded-xl border border-zinc-100 shadow-sm p-5">
            <h2 class="font-semibold text-zinc-900 mb-4">Tren Barang Masuk vs Transaksi Keluar</h2>
            @if (array_sum($masukPerBulan) === 0 && array_sum($keluarPerBulan) === 0)
                <p class="text-sm text-zinc-400 text-center py-16">Belum ada data di tahun anggaran ini.</p>
            @else
                <x-chart type="line" :labels="$labelBulan" :datasets="[
                    [
                        'label' => 'Barang Masuk (BPU)',
                        'data' => $masukPerBulan,
                        'borderColor' => '#059669',
                        'backgroundColor' => '#05966920',
                        'tension' => 0.3,
                    ],
                    [
                        'label' => 'Transaksi Keluar',
                        'data' => $keluarPerBulan,
                        'borderColor' => '#dc2626',
                        'backgroundColor' => '#dc262620',
                        'tension' => 0.3,
                    ],
                ]" />
            @endif
        </div>

        <div class="bg-white rounded-xl border border-zinc-100 shadow-sm p-5">
            <h2 class="font-semibold text-zinc-900 mb-4">Draft vs Selesai</h2>
            @if (array_sum($draftVsSelesai) === 0)
                <p class="text-sm text-zinc-400 text-center py-16">Belum ada transaksi.</p>
            @else
                <x-chart type="doughnut" height="220px" :labels="['Draft', 'Selesai']" :datasets="[['data' => $draftVsSelesai, 'backgroundColor' => ['#d4d4d8', '#059669']]]" />
            @endif
        </div>
    </div>

    <div class="bg-white rounded-xl border border-zinc-100 shadow-sm p-5">
        <h2 class="font-semibold text-zinc-900 mb-4">Top 5 Barang Paling Sering Keluar</h2>
        @if ($topBarang->isEmpty())
            <p class="text-sm text-zinc-400 text-center py-8">Belum ada transaksi keluar di tahun anggaran ini.</p>
        @else
            <x-chart type="bar" :labels="$topBarang->pluck('masterBarang.nama_barang')" :datasets="[
                [
                    'label' => 'Jumlah Keluar',
                    'data' => $topBarang->pluck('total_keluar'),
                    'backgroundColor' => '#059669',
                ],
            ]" :options="['indexAxis' => 'y']" height="220px" />
        @endif
    </div>

    {{-- Surat terakhir --}}
    <div class="bg-white rounded-xl border border-zinc-100 shadow-sm">
        <div class="px-5 py-4 border-b border-zinc-100">
            <h2 class="font-semibold text-zinc-900">Surat Terakhir Digenerate</h2>
        </div>
        <div class="divide-y divide-zinc-100">
            @forelse (Transaksi::where('sekolah_id', auth()->user()->sekolah_id)->where('status', 'selesai')->latest('updated_at')->take(5)->get() as $t)
                <div class="px-5 py-3 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-zinc-900">{{ $t->nomor_npb }}</p>
                        <p class="text-xs text-zinc-500">{{ $t->tanggal_npb->format('d-m-Y') }}</p>
                    </div>
                    <span
                        class="text-xs bg-emerald-50 text-emerald-700 px-2 py-1 rounded-full font-medium">Selesai</span>
                </div>
            @empty
                <div class="px-5 py-8 text-center text-sm text-zinc-400">Belum ada surat yang digenerate.</div>
            @endforelse
        </div>
    </div>

    {{-- Info donasi --}}
    @if ($rekeningDonasi->isNotEmpty())
        <div class="bg-white rounded-xl border border-zinc-100 shadow-sm p-5">
            <h2 class="font-semibold text-zinc-900 flex items-center gap-2">Dukung Kami ❤️</h2>
            <p class="text-sm text-zinc-500 mt-1 mb-4">
                Aplikasi ini gratis buat semua sekolah. Kalau terbantu, donasi seikhlasnya sangat berarti buat
                pengembangan ke depan.
            </p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach ($rekeningDonasi as $rekening)
                    <div class="border border-zinc-100 rounded-lg p-4 flex gap-3 items-center">
                        @if ($rekening->foto)
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($rekening->foto) }}"
                                class="w-14 h-14 rounded object-cover shrink-0">
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
    @endif
</div>
