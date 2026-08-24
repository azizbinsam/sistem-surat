<?php

use App\Models\BarangMasuk;
use App\Models\MasterBarang;
use App\Models\Pegawai;
use App\Models\Transaksi;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    public function with(): array
    {
        $sekolahId = auth()->user()->sekolah_id;

        return [
            'jumlahBarang' => MasterBarang::where('sekolah_id', $sekolahId)->count(),
            'jumlahPegawai' => Pegawai::where('sekolah_id', $sekolahId)->count(),
            'draftMenunggu' => Transaksi::where('sekolah_id', $sekolahId)->where('status', 'draft')->count(),
            'suratBulanIni' => Transaksi::where('sekolah_id', $sekolahId)->where('status', 'selesai')->whereMonth('updated_at', now()->month)->whereYear('updated_at', now()->year)->count(),
            'transaksiTerbaru' => Transaksi::where('sekolah_id', $sekolahId)->where('status', 'selesai')->latest('updated_at')->take(5)->get(),
        ];
    }
}; ?>

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-zinc-900">Selamat datang, {{ auth()->user()->name }} 👋</h1>
        <p class="text-zinc-500 text-sm mt-1">Ringkasan aktivitas sekolah kamu hari ini.</p>
    </div>

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

    <div class="bg-white rounded-xl border border-zinc-100 shadow-sm">
        <div class="px-5 py-4 border-b border-zinc-100">
            <h2 class="font-semibold text-zinc-900">Surat Terakhir Digenerate</h2>
        </div>
        <div class="divide-y divide-zinc-100">
            @forelse ($transaksiTerbaru as $t)
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
</div>
