<?php

use App\Models\BarangMasuk;
use App\Models\BarangMasukItem;
use App\Models\MasterBarang;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    public string $nomor_bpu = '';
    public string $tanggal = '';
    public array $items = [];

    public function mount(): void
    {
        $this->tanggal = now()->format('Y-m-d');
        $this->tambahBaris();
    }

    public function tambahBaris(): void
    {
        $this->items[] = [
            'master_barang_id' => '',
            'spesifikasi' => '',
            'satuan' => '',
            'jumlah' => '',
        ];
    }

    public function hapusBaris(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function updated($name, $value): void
    {
        // auto-isi satuan & spesifikasi default pas pilih barang
        if (preg_match('/^items\.(\d+)\.master_barang_id$/', $name, $m)) {
            $index = (int) $m[1];
            $barang = MasterBarang::find($value);
            if ($barang) {
                $this->items[$index]['satuan'] = $barang->satuan_default;
                if (blank($this->items[$index]['spesifikasi'])) {
                    $this->items[$index]['spesifikasi'] = $barang->spesifikasi_default ?? '';
                }
            }
        }
    }

    public function simpan(): void
    {
        $validated = $this->validate([
            'nomor_bpu' => ['required', 'string', 'max:100', Rule::unique('barang_masuk', 'nomor_bpu')->where('sekolah_id', auth()->user()->sekolah_id)],
            'tanggal' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.master_barang_id' => ['required', 'exists:master_barang,id'],
            'items.*.spesifikasi' => ['required', 'string', 'max:255'],
            'items.*.satuan' => ['required', 'string', 'max:50'],
            'items.*.jumlah' => ['required', 'integer', 'min:1'],
        ]);

        DB::transaction(function () use ($validated) {
            $barangMasuk = BarangMasuk::create([
                'sekolah_id' => auth()->user()->sekolah_id,
                'nomor_bpu' => $validated['nomor_bpu'],
                'tanggal' => $validated['tanggal'],
            ]);

            foreach ($validated['items'] as $item) {
                BarangMasukItem::create([
                    'barang_masuk_id' => $barangMasuk->id,
                    'master_barang_id' => $item['master_barang_id'],
                    'spesifikasi' => $item['spesifikasi'],
                    'satuan' => $item['satuan'],
                    'jumlah' => $item['jumlah'],
                ]);
            }
        });

        session()->flash('success', 'Penerimaan barang berhasil ditambahkan.');
        $this->redirect(route('barang-masuk.index'), navigate: true);
    }

    public function with(): array
    {
        return [
            'daftarMasterBarang' => MasterBarang::where('sekolah_id', auth()->user()->sekolah_id)
                ->orderBy('nama_barang')
                ->get(),
        ];
    }
}; ?>

<div class="max-w-3xl">
    <div class="mb-6">
        <a href="{{ route('barang-masuk.index') }}" wire:navigate
            class="text-sm text-zinc-500 hover:text-zinc-700 inline-flex items-center gap-1 mb-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Kembali
        </a>
        <h1 class="text-2xl font-bold text-zinc-900">Tambah Penerimaan Barang</h1>
        <p class="text-sm text-zinc-500 mt-1">
            Tambahkan penerimaan barang sesuai dengan BPU.
        </p>
    </div>

    <form wire:submit="simpan" class="space-y-6">
        <div class="bg-white p-6 rounded-xl border border-zinc-100 shadow-sm grid sm:grid-cols-2 gap-4">
            <div>
                <x-input-label for="nomor_bpu" value="Nomor BPU" />
                <x-text-input wire:model="nomor_bpu" id="nomor_bpu" class="block mt-1 w-full" type="text" />
                <x-input-error :messages="$errors->get('nomor_bpu')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="tanggal" value="Tanggal" />
                <x-text-input wire:model="tanggal" id="tanggal" class="block mt-1 w-full" type="date" />
                <x-input-error :messages="$errors->get('tanggal')" class="mt-2" />
            </div>
        </div>

        <div class="bg-white rounded-xl border border-zinc-100 shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b border-zinc-100 flex justify-between items-center">
                <h2 class="font-semibold text-zinc-900 text-sm">Daftar Barang</h2>
                <button type="button" wire:click="tambahBaris"
                    class="text-sm text-emerald-600 font-medium hover:underline">+ Tambah Baris</button>
            </div>

            <div class="divide-y divide-zinc-100">
                @foreach ($items as $index => $item)
                    <div wire:key="item-{{ $index }}" class="p-5 grid sm:grid-cols-12 gap-3 items-start">
                        <div class="sm:col-span-4">
                            <x-input-label value="Barang" />
                            <select wire:model.live="items.{{ $index }}.master_barang_id"
                                class="w-full mt-1 text-sm border-zinc-300 rounded-lg">
                                <option value="">-- Pilih --</option>
                                @foreach ($daftarMasterBarang as $mb)
                                    <option value="{{ $mb->id }}">{{ $mb->nama_barang }} ({{ $mb->kode_barang }})
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('items.' . $index . '.master_barang_id')" class="mt-1" />
                        </div>
                        <div class="sm:col-span-3">
                            <x-input-label value="Spesifikasi" />
                            <x-text-input wire:model="items.{{ $index }}.spesifikasi"
                                class="block mt-1 w-full text-sm" type="text" />
                            <x-input-error :messages="$errors->get('items.' . $index . '.spesifikasi')" class="mt-1" />
                        </div>
                        <div class="sm:col-span-2">
                            <x-input-label value="Satuan" />
                            <x-text-input wire:model="items.{{ $index }}.satuan"
                                class="block mt-1 w-full text-sm" type="text" />
                            <x-input-error :messages="$errors->get('items.' . $index . '.satuan')" class="mt-1" />
                        </div>
                        <div class="sm:col-span-2">
                            <x-input-label value="Jumlah" />
                            <x-text-input wire:model="items.{{ $index }}.jumlah"
                                class="block mt-1 w-full text-sm" type="number" min="1" />
                            <x-input-error :messages="$errors->get('items.' . $index . '.jumlah')" class="mt-1" />
                        </div>
                        <div class="sm:col-span-1 flex items-end h-full pb-1">
                            @if (count($items) > 1)
                                <button type="button" wire:click="hapusBaris({{ $index }})"
                                    class="text-red-500 hover:text-red-700 text-sm">✕</button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="flex items-center gap-3">
            <x-primary-button>Simpan</x-primary-button>
            <a href="{{ route('barang-masuk.index') }}" wire:navigate
                class="text-sm text-zinc-500 hover:text-zinc-700">Batal</a>
        </div>
    </form>
</div>
