<?php

use App\Models\MasterBarang;
use App\Services\PersediaanService;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    public string $search = '';

    public function with(PersediaanService $service): array
    {
        $daftarBarang = MasterBarang::where('sekolah_id', auth()->user()->sekolah_id)
            ->when($this->search, fn($q) => $q->where('nama_barang', 'like', "%{$this->search}%"))
            ->orderBy('nama_barang')
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
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-semibold text-zinc-900">Ringkasan Persediaan</h2>
        <a href="{{ route('persediaan.koreksi') }}" wire:navigate>
            <x-secondary-button>+ Koreksi Stok</x-secondary-button>
        </a>
    </div>

    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari nama barang..."
        class="mb-4 w-full border-gray-300 rounded-md shadow-sm focus:border-zinc-500 focus:ring-zinc-500">

    <div class="bg-white rounded-md shadow overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-zinc-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-600 uppercase">Kode</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-600 uppercase">Nama Barang</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-zinc-600 uppercase">Total Masuk</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-zinc-600 uppercase">Total Koreksi</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-zinc-600 uppercase">Total Keluar</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-zinc-600 uppercase">Sisa</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-zinc-600 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($ringkasan as $r)
                    <tr wire:key="stok-{{ $r->barang->id }}">
                        <td class="px-4 py-2 text-sm">{{ $r->barang->kode_barang }}</td>
                        <td class="px-4 py-2 text-sm">{{ $r->barang->nama_barang }}</td>
                        <td class="px-4 py-2 text-sm text-right">{{ $r->total_masuk }}</td>
                        <td class="px-4 py-2 text-sm text-right">{{ $r->total_koreksi }}</td>
                        <td class="px-4 py-2 text-sm text-right">{{ $r->total_keluar }}</td>
                        <td
                            class="px-4 py-2 text-sm text-right font-semibold {{ $r->sisa < 0 ? 'text-red-600' : 'text-zinc-900' }}">
                            {{ $r->sisa }} {{ $r->barang->satuan_default }}
                        </td>
                        <td class="px-4 py-2 text-sm text-right">
                            <a href="{{ route('persediaan.riwayat', $r->barang) }}" wire:navigate
                                class="text-zinc-700 hover:underline">Riwayat</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-500">Belum ada data barang.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $daftarBarang->links() }}</div>
</div>
