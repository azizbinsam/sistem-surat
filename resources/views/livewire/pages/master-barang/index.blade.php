<?php

use App\Models\MasterBarang;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Livewire\Concerns\HasCustomPagination;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination, HasCustomPagination;

    public string $search = '';
    public string $filterKategori = '';
    public string $sortBy = 'nama_barang';
    public string $sortDir = 'asc';

    protected array $kolomBolehSort = ['kode_barang', 'nama_barang', 'kategori', 'satuan_default'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterKategori(): void
    {
        $this->resetPage();
    }

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
                ->when($this->filterKategori, fn($q) => $q->where('kategori', $this->filterKategori))
                ->orderBy($this->sortBy, $this->sortDir)
                ->paginate(10),
            'daftarKategori' => MasterBarang::where('sekolah_id', auth()->user()->sekolah_id)
                ->whereNotNull('kategori')
                ->where('kategori', '!=', '')
                ->distinct()
                ->orderBy('kategori')
                ->pluck('kategori'),
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

    <div class="mb-4 flex flex-col sm:flex-row gap-3">
        <div class="relative max-w-sm flex-1">
            <svg class="w-4 h-4 text-zinc-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari nama/kode barang..."
                class="w-full pl-10 pr-4 py-2.5 border-zinc-200 rounded-lg shadow-sm text-sm focus:border-emerald-500 focus:ring-emerald-500">
        </div>
        <select wire:model.live="filterKategori"
            class="w-full sm:w-56 py-2.5 border-zinc-200 rounded-lg shadow-sm text-sm focus:border-emerald-500 focus:ring-emerald-500">
            <option value="">Semua Kategori</option>
            @foreach ($daftarKategori as $kategori)
                <option value="{{ $kategori }}">{{ $kategori }}</option>
            @endforeach
        </select>
    </div>

    <div class="bg-white rounded-xl border border-zinc-100 shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-zinc-100">
            <thead class="bg-zinc-50">
                <tr>
                    <x-th-sortable column="kode_barang" :sortBy="$sortBy" :sortDir="$sortDir">Kode</x-th-sortable>
                    <x-th-sortable column="nama_barang" :sortBy="$sortBy" :sortDir="$sortDir">Nama Barang</x-th-sortable>
                    <x-th-sortable column="kategori" :sortBy="$sortBy" :sortDir="$sortDir">Kategori</x-th-sortable>
                    <x-th-sortable column="satuan_default" :sortBy="$sortBy" :sortDir="$sortDir">Satuan</x-th-sortable>
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
                            <p class="text-sm text-zinc-500">
                                @if ($search || $filterKategori)
                                    Tidak ada barang yang cocok dengan pencarian/filter.
                                @else
                                    Belum ada data barang.
                                @endif
                            </p>
                            @if (!$search && !$filterKategori)
                                <a href="{{ route('master-barang.create') }}" wire:navigate
                                    class="text-sm text-emerald-600 font-medium hover:underline mt-1 inline-block">+ Tambah
                                    barang pertama</a>
                            @endif
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