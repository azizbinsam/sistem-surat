<?php

use App\Models\BarangMasuk;
use App\Services\PersediaanService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    public ?int $bpuAkanDihapus = null;
    public string $nomorBpuAkanDihapus = '';
    public string $warningHapus = '';

    /**
     * Klik pertama: cek dampak ke ledger dulu sebelum beneran hapus.
     * Kalau ada barang yang sisanya jadi minus (berarti udah kepake di transaksi keluar),
     * tampilkan warning + minta konfirmasi eksplisit lagi.
     */
    public function konfirmasiHapus(int $id, PersediaanService $service): void
    {
        $bpu = BarangMasuk::where('sekolah_id', auth()->user()->sekolah_id)
            ->with('items.masterBarang')
            ->findOrFail($id);

        $peringatan = [];
        foreach ($bpu->items as $item) {
            $sisaSetelahHapus = $service->sisaSaatIni($item->master_barang_id) - $item->jumlah;
            if ($sisaSetelahHapus < 0) {
                $peringatan[] = "{$item->masterBarang->nama_barang} (sisa jadi {$sisaSetelahHapus})";
            }
        }

        if (!empty($peringatan)) {
            $this->bpuAkanDihapus = $id;
            $this->nomorBpuAkanDihapus = $bpu->nomor_bpu;
            $this->warningHapus = 'Menghapus BPU ini bikin stok minus: ' . implode(', ', $peringatan) . '. Artinya udah ada transaksi keluar yang kepake dari barang ini — cek dulu sebelum lanjut.';
            return;
        }

        $this->hapus($id);
    }

    public function hapus(int $id): void
    {
        $bpu = BarangMasuk::where('sekolah_id', auth()->user()->sekolah_id)->findOrFail($id);

        DB::transaction(function () use ($bpu) {
            $bpu->delete(); // barang_masuk_item ikut kehapus (cascadeOnDelete)
        });

        $this->batalHapus();

        session()->flash('success', 'Penerimaan barang berhasil dihapus.');
    }

    public function batalHapus(): void
    {
        $this->bpuAkanDihapus = null;
        $this->nomorBpuAkanDihapus = '';
        $this->warningHapus = '';
    }

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

    @if ($warningHapus)
        <div class="mb-4 p-4 bg-amber-50 border border-amber-200 rounded-lg">
            <p class="text-sm text-amber-800 font-medium">⚠ BPU {{ $nomorBpuAkanDihapus }}: {{ $warningHapus }}</p>
            <div class="mt-2 flex gap-2">
                <button wire:click="hapus({{ $bpuAkanDihapus }})"
                    class="text-sm bg-amber-600 text-white px-3 py-1.5 rounded-lg hover:bg-amber-500">
                    Ya, Tetap Hapus
                </button>
                <button wire:click="batalHapus" class="text-sm text-zinc-600 px-3 py-1.5 rounded-lg hover:bg-zinc-100">
                    Batal
                </button>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-md shadow overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-zinc-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-600 uppercase">Nomor BPU</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-600 uppercase">Tanggal</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-600 uppercase">Jumlah Item</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-600 uppercase">Rincian Barang</th>
                    <th class="px-5 py-3 text-right text-xs font-semibold text-zinc-500 uppercase tracking-wider">Aksi
                    </th>
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
                        <td class="px-5 py-3.5 text-sm text-right space-x-3">
                            <a href="{{ route('barang-masuk.edit', $bpu) }}" wire:navigate
                                class="text-zinc-500 hover:text-emerald-600 font-medium">Edit</a>
                            <button wire:click="konfirmasiHapus({{ $bpu->id }})"
                                wire:confirm="Yakin mau hapus BPU {{ $bpu->nomor_bpu }}?"
                                class="text-red-500 hover:text-red-700 font-medium">Hapus</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">Belum ada data penerimaan
                            barang.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $daftarBpu->links() }}</div>
</div>
