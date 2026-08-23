<?php

use App\Models\BarangMasuk;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    public function with(): array
    {
        return [
            'daftarBpu' => BarangMasuk::where('sekolah_id', auth()->user()->sekolah_id)
                ->withCount('items')
                ->with('items') // buat hitung total jumlah barang per BPU di view
                ->orderByDesc('tanggal')
                ->paginate(10),
        ];
    }
}; ?>

<div>
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-semibold text-zinc-900">Riwayat Penerimaan Barang (BPU)</h2>
        <a href="{{ route('barang-masuk.upload') }}" wire:navigate>
            <x-primary-button>+ Upload BPU</x-primary-button>
        </a>
    </div>

    @if (session('success'))
        <div class="mb-4 p-3 bg-zinc-100 text-zinc-800 rounded-md text-sm">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-md shadow overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-zinc-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-600 uppercase">Nomor BPU</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-600 uppercase">Tanggal</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-600 uppercase">Jumlah Item</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-600 uppercase">Rincian Barang</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($daftarBpu as $bpu)
                    <tr wire:key="bpu-{{ $bpu->id }}">
                        <td class="px-4 py-2 text-sm font-medium">{{ $bpu->nomor_bpu }}</td>
                        <td class="px-4 py-2 text-sm">{{ $bpu->tanggal->format('d-m-Y') }}</td>
                        <td class="px-4 py-2 text-sm">{{ $bpu->items_count }} item</td>
                        <td class="px-4 py-2 text-sm text-gray-600">
                            {{ $bpu->items->map(fn($i) => "{$i->masterBarang->nama_barang} ({$i->jumlah} {$i->satuan})")->implode(', ') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-sm text-gray-500">Belum ada data penerimaan
                            barang.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $daftarBpu->links() }}</div>
</div>
