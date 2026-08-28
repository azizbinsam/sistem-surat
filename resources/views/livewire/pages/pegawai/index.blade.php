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
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-semibold text-zinc-900">Data Pegawai</h2>
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
        <div class="mb-4 p-3 bg-zinc-100 text-zinc-800 rounded-md text-sm">{{ session('success') }}</div>
    @endif

    <div class="mb-4 flex flex-col sm:flex-row gap-3">
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari nama pegawai..."
            class="flex-1 border-gray-300 rounded-md shadow-sm focus:border-zinc-500 focus:ring-zinc-500">
        <select wire:model.live="filterKategori"
            class="w-full sm:w-56 border-gray-300 rounded-md shadow-sm focus:border-zinc-500 focus:ring-zinc-500">
            <option value="">Semua Kategori</option>
            @foreach ($labelKategori as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="bg-white rounded-md shadow overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-zinc-50">
                <tr>
                    <x-th-sortable column="nama" :sortBy="$sortBy" :sortDir="$sortDir">Nama</x-th-sortable>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-600 uppercase">NIP</th>
                    <x-th-sortable column="jabatan" :sortBy="$sortBy" :sortDir="$sortDir">Jabatan</x-th-sortable>
                    <x-th-sortable column="kategori" :sortBy="$sortBy" :sortDir="$sortDir">Kategori</x-th-sortable>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-600 uppercase">TTD</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-zinc-600 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($daftarPegawai as $pegawai)
                    <tr wire:key="pegawai-{{ $pegawai->id }}">
                        <td class="px-4 py-2 text-sm">{{ $pegawai->nama }}</td>
                        <td class="px-4 py-2 text-sm">{{ $pegawai->nip ?? '-' }}</td>
                        <td class="px-4 py-2 text-sm">{{ $pegawai->jabatan }}</td>
                        <td class="px-4 py-2 text-sm">{{ $labelKategori[$pegawai->kategori] }}</td>
                        <td class="px-4 py-2 text-sm">
                            @if ($pegawai->ttd_path)
                                <span class="text-zinc-700">✓ Sudah</span>
                            @else
                                <span class="text-red-500">Belum ada</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-sm text-right space-x-2">
                            <a href="{{ route('pegawai.edit', $pegawai) }}" wire:navigate
                                class="text-zinc-700 hover:underline">Edit</a>
                            <button wire:click="hapus({{ $pegawai->id }})" wire:confirm="Yakin hapus pegawai ini?"
                                class="text-red-600 hover:underline">Hapus</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500">
                            @if ($search || $filterKategori)
                                Tidak ada pegawai yang cocok dengan pencarian/filter.
                            @else
                                Belum ada data pegawai.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $daftarPegawai->links() }}</div>
</div>