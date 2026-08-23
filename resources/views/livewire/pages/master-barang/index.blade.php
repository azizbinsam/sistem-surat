<?php

use App\Models\MasterBarang;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    public string $search = '';

    public function hapus(MasterBarang $masterBarang): void
    {
        // pastikan barang ini milik sekolah yang sedang login (proteksi tenant)
        if ($masterBarang->sekolah_id !== auth()->user()->sekolah_id) {
            abort(403);
        }

        $masterBarang->delete(); // soft delete
        session()->flash('success', 'Barang berhasil dihapus.');
    }

    public function with(): array
    {
        return [
            'daftarBarang' => MasterBarang::where('sekolah_id', auth()->user()->sekolah_id)
                ->when($this->search, fn($q) => $q->where('nama_barang', 'like', "%{$this->search}%")->orWhere('kode_barang', 'like', "%{$this->search}%"))
                ->orderBy('nama_barang')
                ->paginate(10),
        ];
    }
}; ?>

<div>
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-semibold text-zinc-900">Master Barang</h2>
        <div class="flex gap-2">
            <a href="{{ route('master-barang.import') }}" wire:navigate>
                <x-secondary-button>Import Excel</x-secondary-button>
            </a>
            <a href="{{ route('master-barang.create') }}" wire:navigate>
                <x-primary-button>+ Tambah Barang</x-primary-button>
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-4 p-3 bg-zinc-100 text-zinc-800 rounded-md text-sm">
            {{ session('success') }}
        </div>
    @endif

    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari nama/kode barang..."
        class="mb-4 w-full border-gray-300 rounded-md shadow-sm focus:border-zinc-500 focus:ring-zinc-500">

    <div class="bg-white rounded-md shadow overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-zinc-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-600 uppercase">Kode</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-600 uppercase">Nama Barang</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-600 uppercase">Kategori</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-600 uppercase">Satuan</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-zinc-600 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($daftarBarang as $barang)
                    <tr wire:key="barang-{{ $barang->id }}">
                        <td class="px-4 py-2 text-sm">{{ $barang->kode_barang }}</td>
                        <td class="px-4 py-2 text-sm">{{ $barang->nama_barang }}</td>
                        <td class="px-4 py-2 text-sm">{{ $barang->kategori ?? '-' }}</td>
                        <td class="px-4 py-2 text-sm">{{ $barang->satuan_default }}</td>
                        <td class="px-4 py-2 text-sm text-right space-x-2">
                            <a href="{{ route('master-barang.edit', $barang) }}" wire:navigate
                                class="text-zinc-700 hover:underline">Edit</a>
                            <button wire:click="hapus({{ $barang->id }})" wire:confirm="Yakin hapus barang ini?"
                                class="text-red-600 hover:underline">Hapus</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">Belum ada data barang.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $daftarBarang->links() }}
    </div>
</div>
