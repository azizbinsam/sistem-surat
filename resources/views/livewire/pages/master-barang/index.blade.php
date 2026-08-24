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
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900">Master Barang</h1>
            <p class="text-sm text-zinc-500 mt-1">Kelola katalog kode barang sekolah kamu.</p>
        </div>
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
        <div class="mb-4 p-3 bg-emerald-50 text-emerald-700 rounded-lg text-sm border border-emerald-100">
            {{ session('success') }}</div>
    @endif

    <div class="mb-4 relative max-w-sm">
        <svg class="w-4 h-4 text-zinc-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor"
            viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari nama/kode barang..."
            class="w-full pl-10 pr-4 py-2.5 border-zinc-200 rounded-lg shadow-sm text-sm focus:border-emerald-500 focus:ring-emerald-500">
    </div>

    <div class="bg-white rounded-xl border border-zinc-100 shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-zinc-100">
            <thead class="bg-zinc-50">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-zinc-500 uppercase tracking-wider">Kode
                    </th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-zinc-500 uppercase tracking-wider">Nama
                        Barang</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-zinc-500 uppercase tracking-wider">
                        Kategori</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-zinc-500 uppercase tracking-wider">Satuan
                    </th>
                    <th class="px-5 py-3 text-right text-xs font-semibold text-zinc-500 uppercase tracking-wider">Aksi
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse ($daftarBarang as $barang)
                    <tr wire:key="barang-{{ $barang->id }}" class="hover:bg-zinc-50 transition">
                        <td class="px-5 py-3.5 text-sm text-zinc-600 font-mono">{{ $barang->kode_barang }}</td>
                        <td class="px-5 py-3.5 text-sm font-medium text-zinc-900">{{ $barang->nama_barang }}</td>
                        <td class="px-5 py-3.5 text-sm text-zinc-500">{{ $barang->kategori ?? '-' }}</td>
                        <td class="px-5 py-3.5 text-sm text-zinc-500">{{ $barang->satuan_default }}</td>
                        <td class="px-5 py-3.5 text-sm text-right space-x-3">
                            <a href="{{ route('master-barang.edit', $barang) }}" wire:navigate
                                class="text-zinc-500 hover:text-emerald-600 font-medium">Edit</a>
                            <button wire:click="hapus({{ $barang->id }})" wire:confirm="Yakin hapus barang ini?"
                                class="text-red-500 hover:text-red-700 font-medium">Hapus</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-16 text-center">
                            <svg class="w-10 h-10 text-zinc-300 mx-auto mb-3" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                            <p class="text-sm text-zinc-500">Belum ada data barang.</p>
                            <a href="{{ route('master-barang.create') }}" wire:navigate
                                class="text-sm text-emerald-600 font-medium hover:underline mt-1 inline-block">+ Tambah
                                barang pertama</a>
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
