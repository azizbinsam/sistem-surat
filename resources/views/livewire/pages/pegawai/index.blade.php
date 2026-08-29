<?php

use App\Models\Pegawai;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Livewire\Concerns\HasCustomPagination;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination, HasCustomPagination;

    public string $search = '';
    public string $filterKategori = '';
    public string $sortBy = 'nama';
    public string $sortDir = 'asc';

    protected array $kolomBolehSort = ['nama', 'jabatan', 'kategori'];

    protected array $labelKategori = [
        'kepala_sekolah' => 'Kepala Sekolah',
        'pengurus_barang_pembantu' => 'Pengurus Barang Pembantu',
        'guru' => 'Guru',
        'tendik' => 'Tendik',
    ];

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

    public function hapus(Pegawai $pegawai): void
    {
        if ($pegawai->sekolah_id !== auth()->user()->sekolah_id) {
            abort(403);
        }

        $pegawai->delete();
        session()->flash('success', 'Pegawai berhasil dihapus.');
    }

    public function with(): array
    {
        return [
            'daftarPegawai' => Pegawai::where('sekolah_id', auth()->user()->sekolah_id)
                ->when($this->search, fn($q) => $q->where('nama', 'like', "%{$this->search}%"))
                ->when($this->filterKategori, fn($q) => $q->where('kategori', $this->filterKategori))
                ->orderBy($this->sortBy, $this->sortDir)
                ->paginate(10),
            'labelKategori' => $this->labelKategori,
        ];
    }
}; ?>

<div>
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900">Data Pegawai</h1>
            <p class="text-sm text-zinc-500 mt-1">Kelola data pegawai sekolah.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('pegawai.import') }}" wire:navigate>
                <x-secondary-button>Import Excel</x-secondary-button>
            </a>
            <a href="{{ route('pegawai.create') }}" wire:navigate>
                <x-primary-button>+ Tambah Pegawai</x-primary-button>
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-4 p-3 bg-emerald-50 text-emerald-700 rounded-lg text-sm border border-emerald-100">
            {{ session('success') }}</div>
    @endif

    <div class="mb-4 flex flex-col sm:flex-row gap-3">
        <div class="relative max-w-sm flex-1">
            <svg class="w-4 h-4 text-zinc-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none"
                stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari nama pegawai..."
                class="w-full pl-10 pr-4 py-2.5 border-zinc-200 rounded-lg shadow-sm text-sm focus:border-emerald-500 focus:ring-emerald-500">
        </div>
        <select wire:model.live="filterKategori"
            class="w-full sm:w-56 py-2.5 border-zinc-200 rounded-lg shadow-sm text-sm focus:border-emerald-500 focus:ring-emerald-500">
            <option value="">Semua Kategori</option>
            @foreach ($labelKategori as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="bg-white rounded-xl border border-zinc-100 shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-zinc-100">
            <thead class="bg-zinc-50">
                <tr>
                    <x-th-sortable column="nama" :sortBy="$sortBy" :sortDir="$sortDir">Nama</x-th-sortable>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 uppercase tracking-wider">NIP
                    </th>
                    <x-th-sortable column="jabatan" :sortBy="$sortBy" :sortDir="$sortDir">Jabatan</x-th-sortable>
                    <x-th-sortable column="kategori" :sortBy="$sortBy" :sortDir="$sortDir">Kategori</x-th-sortable>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 uppercase tracking-wider">TTD
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-zinc-500 uppercase tracking-wider">Aksi
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($daftarPegawai as $pegawai)
                    <tr wire:key="pegawai-{{ $pegawai->id }}">
                        <td class="px-4 py-3 text-sm font-medium text-zinc-900">{{ $pegawai->nama }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-zinc-900">{{ $pegawai->nip ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-zinc-900">{{ $pegawai->jabatan }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-zinc-900">{{ $labelKategori[$pegawai->kategori] }}
                        </td>
                        <td class="px-4 py-3 text-sm">
                            @if ($pegawai->ttd_path)
                                <span
                                    class="px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">Ada</span>
                            @else
                                <span
                                    class="px-2 py-0.5 rounded-full text-xs font-medium bg-zinc-100 text-zinc-600">Belum
                                    ada</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-sm text-right space-x-2">
                            <a href="{{ route('pegawai.edit', $pegawai) }}" wire:navigate
                                class="text-zinc-500 hover:text-emerald-600 font-medium">Edit</a>
                            <button wire:click="hapus({{ $pegawai->id }})" wire:confirm="Yakin hapus pegawai ini?"
                                class="text-red-500 hover:text-red-700 font-medium">Hapus</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-16 text-center">
                            <svg class="w-10 h-10 text-zinc-300 mx-auto mb-3" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1a4 4 0 100-8 4 4 0 000 8zm6 3a4 4 0 00-3-3.87" />
                            </svg>
                            <p class="text-sm text-zinc-500">
                                @if ($search || $filterKategori)
                                    Tidak ada pegawai yang cocok dengan pencarian/filter.
                                @else
                                    Belum ada data pegawai.
                                @endif
                            </p>
                            @if (!$search && !$filterKategori)
                                <a href="{{ route('pegawai.create') }}" wire:navigate
                                    class="text-sm text-emerald-600 font-medium hover:underline mt-1 inline-block">+
                                    Tambah
                                    pegawai</a>
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $daftarPegawai->links() }}</div>
</div>
