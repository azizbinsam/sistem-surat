<?php

use App\Models\MasterBarang;
use App\Services\PersediaanService;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Livewire\Concerns\HasCustomPagination;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination, HasCustomPagination;

    public string $search = '';
    public string $sortBy = 'nama_barang';
    public string $sortDir = 'asc';

    protected array $kolomBolehSort = ['nama_barang', 'kode_barang'];

    public function sortir(string $kolom): void
    {
        if (!in_array($kolom, $this->kolomBolehSort, true)) {
            return;
        }

        if ($this->sortBy === $kolom) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $kolom;
            $this->sortDir = 'asc';
        }
    }

    public function with(PersediaanService $service): array
    {
        $daftarBarang = MasterBarang::where('sekolah_id', auth()->user()->sekolah_id)
            ->when($this->search, fn($q) => $q->where('nama_barang', 'like', "%{$this->search}%"))
            ->orderBy($this->sortBy, $this->sortDir)
            ->paginate(15);

        // hitung ledger per barang di halaman ini aja (bukan semua barang sekaligus, biar ringan)
        $ringkasan = $daftarBarang->getCollection()->map(function ($barang) use ($service) {
            return (object) [
                'barang' => $barang,
                'total_masuk' => $service->totalMasuk($barang->id),
                'total_koreksi' => $service->totalKoreksi($barang->id),
                'total_keluar' => $service->totalKeluar($barang->id),
                'sisa' => $service->sisaSaatIni($barang->id),
            ];
        });

        return [
            'daftarBarang' => $daftarBarang,
            'ringkasan' => $ringkasan,
        ];
    }
}; ?>

<div>
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900">Ringkasan Persediaan</h1>
            <p class="text-sm text-zinc-500 mt-1">Cek saldo persediaan dan pastikan jangan lebih atau kurang.</p>
        </div>
        <a href="{{ route('persediaan.koreksi') }}" wire:navigate>
            <x-secondary-button>+ Koreksi Stok</x-secondary-button>
        </a>
    </div>
    <div class="mb-4 flex flex-col sm:flex-row gap-3">
        <div class="relative max-w-sm flex-1">
            <svg class="w-4 h-4 text-zinc-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none"
                stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari nama barang..."
                class="w-full pl-10 pr-4 py-2.5 border-zinc-200 rounded-lg shadow-sm text-sm focus:border-emerald-500 focus:ring-emerald-500">
        </div>
    </div>

    <div class="bg-white rounded-xl border border-zinc-100 shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-zinc-100">
            <thead class="bg-zinc-50">
                <tr>
                    <x-th-sortable column="kode_barang" :sortBy="$sortBy" :sortDir="$sortDir">Kode</x-th-sortable>
                    <x-th-sortable column="nama_barang" :sortBy="$sortBy" :sortDir="$sortDir">Nama
                        Barang</x-th-sortable>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-zinc-500 uppercase tracking-wider">Total
                        Masuk</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-zinc-500 uppercase tracking-wider">Total
                        Koreksi</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-zinc-500 uppercase tracking-wider">Total
                        Keluar</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-zinc-500 uppercase tracking-wider">Sisa
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-zinc-500 uppercase tracking-wider">Aksi
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse ($ringkasan as $r)
                    <tr wire:key="stok-{{ $r->barang->id }}">
                        <td class="px-4 py-3 text-sm font-medium">{{ $r->barang->kode_barang }}</td>
                        <td class="px-4 py-3 text-sm font-medium">{{ $r->barang->nama_barang }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-right">{{ $r->total_masuk }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-right">{{ $r->total_koreksi }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-right">{{ $r->total_keluar }}</td>
                        <td
                            class="px-4 py-3 text-sm text-right font-semibold {{ $r->sisa < 0 ? 'text-red-600' : 'text-zinc-900' }}">
                            {{ $r->sisa }} {{ $r->barang->satuan_default }}
                        </td>
                        <td class="px-4 py-3 text-sm font-medium text-right">
                            <a href="{{ route('persediaan.riwayat', $r->barang) }}" wire:navigate
                                class="text-zinc-500 hover:text-emerald-600 font-medium">Riwayat</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-16 text-center">
                            <svg class="w-10 h-10 text-zinc-300 mx-auto mb-3" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                            <p class="text-sm text-zinc-500">
                                @if ($search)
                                    Tidak ada barang yang cocok dengan pencarian.
                                @else
                                    Belum ada data barang.
                                @endif
                            </p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $daftarBarang->links() }}</div>
</div>
