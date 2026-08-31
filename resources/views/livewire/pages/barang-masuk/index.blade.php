<?php

use App\Models\BarangMasuk;
use App\Services\PersediaanService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Livewire\Concerns\HasCustomPagination;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination, HasCustomPagination;

    public string $search = '';
    public string $sortBy = 'tanggal';
    public string $sortDir = 'desc';
    public string $perPage = '10';

    protected array $kolomBolehSort = ['nomor_bpu', 'tanggal'];

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
        $this->selected = [];
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

    // ===== Hapus (satuan & bulk), lewat modal konfirmasi. Cek dampak ledger dulu:
    // kalau ada barang yang sisanya jadi minus (berarti udah kepake di transaksi
    // keluar), tampilkan peringatan di dalam modal sebelum user konfirmasi.
    public array $selected = [];
    public ?int $idHapusSatuan = null;
    public bool $modeHapusBulk = false;
    public bool $modalHapusTampil = false;
    public array $peringatanStok = [];
    public string $nomorBpuDihapus = '';

    public function mintaHapusSatuan(int $id, PersediaanService $service): void
    {
        $bpu = BarangMasuk::where('sekolah_id', auth()->user()->sekolah_id)->findOrFail($id);

        $this->idHapusSatuan = $id;
        $this->modeHapusBulk = false;
        $this->nomorBpuDihapus = $bpu->nomor_bpu;
        $this->peringatanStok = $this->hitungPeringatanStok([$id], $service);
        $this->modalHapusTampil = true;
    }

    public function mintaHapusBulk(PersediaanService $service): void
    {
        if (empty($this->selected)) {
            return;
        }

        $this->modeHapusBulk = true;
        $this->peringatanStok = $this->hitungPeringatanStok($this->selected, $service);
        $this->modalHapusTampil = true;
    }

    protected function hitungPeringatanStok(array $bpuIds, PersediaanService $service): array
    {
        $bpuList = BarangMasuk::where('sekolah_id', auth()->user()->sekolah_id)
            ->whereIn('id', $bpuIds)
            ->with('items.masterBarang')
            ->get();

        $peringatan = [];
        foreach ($bpuList as $bpu) {
            foreach ($bpu->items as $item) {
                $sisaSetelahHapus = $service->sisaSaatIni($item->master_barang_id) - $item->jumlah;
                if ($sisaSetelahHapus < 0) {
                    $peringatan[] = "{$item->masterBarang->nama_barang} (dari BPU {$bpu->nomor_bpu}, sisa jadi {$sisaSetelahHapus})";
                }
            }
        }

        return $peringatan;
    }

    public function batalHapus(): void
    {
        $this->idHapusSatuan = null;
        $this->modeHapusBulk = false;
        $this->modalHapusTampil = false;
        $this->peringatanStok = [];
        $this->nomorBpuDihapus = '';
    }

    public function eksekusiHapus(): void
    {
        $sekolahId = auth()->user()->sekolah_id;

        if ($this->modeHapusBulk) {
            $ids = $this->selected;
            $jumlah = count($ids);

            DB::transaction(function () use ($sekolahId, $ids) {
                // barang_masuk_item ikut kehapus (cascadeOnDelete)
                BarangMasuk::where('sekolah_id', $sekolahId)->whereIn('id', $ids)->delete();
            });

            session()->flash('success', "{$jumlah} data penerimaan barang berhasil dihapus.");
            $this->selected = [];
        } else {
            $bpu = BarangMasuk::where('sekolah_id', $sekolahId)->findOrFail($this->idHapusSatuan);

            DB::transaction(function () use ($bpu) {
                $bpu->delete(); // barang_masuk_item ikut kehapus (cascadeOnDelete)
            });

            session()->flash('success', 'Penerimaan barang berhasil dihapus.');
        }

        $this->batalHapus();
    }

    public function with(): array
    {
        return [
            'daftarBpu' => BarangMasuk::where('sekolah_id', auth()->user()->sekolah_id)
                ->when($this->search, fn($q) => $q->where('nomor_bpu', 'like', "%{$this->search}%"))
                ->withCount('items')
                ->with('items') // buat hitung total jumlah barang per BPU di view
                ->orderBy($this->sortBy, $this->sortDir)
                ->paginate($this->perPage === 'semua' ? 100000 : (int) $this->perPage),
        ];
    }
}; ?>

<div>
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900">Penerimaan Barang</h1>
            <p class="text-sm text-zinc-500 mt-1">Riwayat penerimaan barang sesuai dengan BPU.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('barang-masuk.create') }}" wire:navigate>
                <x-secondary-button>+ Tambah Manual</x-secondary-button>
            </a>
            <a href="{{ route('barang-masuk.upload') }}" wire:navigate>
                <x-primary-button>+ Upload BPU</x-primary-button>
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-4 p-3 bg-zinc-100 text-zinc-800 rounded-md text-sm">{{ session('success') }}</div>
    @endif

    <div class="mb-4 flex flex-col sm:flex-row gap-3">
        <div class="relative max-w-sm flex-1">
            <svg class="w-4 h-4 text-zinc-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none"
                stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari nomor BPU..."
                class="w-full pl-10 pr-4 py-2.5 border-zinc-200 rounded-lg shadow-sm text-sm focus:border-emerald-500 focus:ring-emerald-500">
        </div>
        <x-per-page-selector />
    </div>

    @if (count($selected) > 0)
        <div class="mb-3 p-3 bg-zinc-100 rounded-lg flex items-center justify-between">
            <span class="text-sm text-zinc-700">{{ count($selected) }} BPU dipilih</span>
            <button wire:click="mintaHapusBulk"
                class="px-3 py-1.5 bg-red-600 text-white text-xs font-medium rounded-lg hover:bg-red-500">
                Hapus Terpilih
            </button>
        </div>
    @endif

    <div class="bg-white rounded-md shadow overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-zinc-50">
                <tr>
                    <th class="pl-4 pr-2 py-3 w-10">
                        <input type="checkbox" class="rounded"
                            wire:click="$set('selected', $event.target.checked ? {{ $daftarBpu->pluck('id') }} : [])">
                    </th>
                    <x-th-sortable column="nomor_bpu" :sortBy="$sortBy" :sortDir="$sortDir">Nomor BPU</x-th-sortable>
                    <x-th-sortable column="tanggal" :sortBy="$sortBy" :sortDir="$sortDir">Tanggal</x-th-sortable>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-600 uppercase">Jumlah Item</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-600 uppercase">Rincian Barang</th>
                    <th class="px-5 py-3 text-right text-xs font-semibold text-zinc-500 uppercase tracking-wider">Aksi
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($daftarBpu as $bpu)
                    <tr wire:key="bpu-{{ $bpu->id }}">
                        <td class="pl-4 pr-2 py-2">
                            <input type="checkbox" class="rounded" wire:model="selected" value="{{ $bpu->id }}">
                        </td>
                        <td class="px-4 py-2 text-sm font-medium">{{ $bpu->nomor_bpu }}</td>
                        <td class="px-4 py-2 text-sm">{{ $bpu->tanggal->format('d-m-Y') }}</td>
                        <td class="px-4 py-2 text-sm">{{ $bpu->items_count }} item</td>
                        <td class="px-4 py-2 text-sm text-gray-600">
                            {{ $bpu->items->map(fn($i) => "{$i->masterBarang->nama_barang} ({$i->jumlah} {$i->satuan})")->implode(', ') }}
                        </td>
                        <td class="px-5 py-3.5 text-sm text-right space-x-3">
                            <a href="{{ route('barang-masuk.edit', $bpu) }}" wire:navigate
                                class="text-zinc-500 hover:text-emerald-600 font-medium">Edit</a>
                            <button wire:click="mintaHapusSatuan({{ $bpu->id }})"
                                class="text-red-500 hover:text-red-700 font-medium">Hapus</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-16 text-center">
                            <svg class="w-10 h-10 text-zinc-300 mx-auto mb-3" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                            <p class="text-sm text-zinc-500">
                                @if ($search)
                                    Tidak ada BPU yang cocok dengan pencarian.
                                @else
                                    Belum ada data penerimaan barang.
                                @endif
                            </p>
                            @if (!$search)
                                <a href="{{ route('barang-masuk.create') }}" wire:navigate
                                    class="text-sm text-emerald-600 font-medium hover:underline mt-1 inline-block">
                                    + Tambah
                                    BPU pertama</a>
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $daftarBpu->links() }}</div>

    <x-modal-konfirmasi-hapus :show="$modalHapusTampil" :title="$modeHapusBulk ? 'Hapus ' . count($selected) . ' BPU?' : 'Hapus BPU Ini?'">
        @if ($modeHapusBulk)
            <p>{{ count($selected) }} data penerimaan barang yang dipilih akan dihapus. Tindakan ini tidak bisa
                dibatalkan.</p>
        @else
            <p>BPU <strong>{{ $nomorBpuDihapus }}</strong> akan dihapus. Tindakan ini tidak bisa dibatalkan.</p>
        @endif

        @if (!empty($peringatanStok))
            <div class="mt-3 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                <p class="text-xs font-medium text-amber-800 mb-1">⚠ Menghapus ini bikin stok minus:</p>
                <ul class="text-xs text-amber-700 list-disc list-inside space-y-0.5">
                    @foreach ($peringatanStok as $peringatan)
                        <li>{{ $peringatan }}</li>
                    @endforeach
                </ul>
                <p class="text-xs text-amber-700 mt-1.5">Artinya udah ada transaksi keluar yang kepake dari barang
                    ini — cek dulu sebelum lanjut.</p>
            </div>
        @endif
    </x-modal-konfirmasi-hapus>
</div>