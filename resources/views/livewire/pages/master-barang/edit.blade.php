<?php

use App\Models\MasterBarang;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    public MasterBarang $masterBarang;

    public string $kode_barang = '';
    public string $nama_barang = '';
    public string $kategori = '';
    public string $satuan_default = '';
    public string $keperluan_default = '';

    public function mount(MasterBarang $masterBarang): void
    {
        // proteksi tenant: barang ini harus milik sekolah yang lagi login
        if ($masterBarang->sekolah_id !== auth()->user()->sekolah_id) {
            abort(403);
        }

        $this->masterBarang = $masterBarang;
        $this->kode_barang = $masterBarang->kode_barang;
        $this->nama_barang = $masterBarang->nama_barang;
        $this->kategori = $masterBarang->kategori ?? '';
        $this->satuan_default = $masterBarang->satuan_default;
        $this->keperluan_default = $masterBarang->keperluan_default ?? '';
    }

    public function simpan(): void
    {
        $validated = $this->validate([
            'kode_barang' => ['required', 'string', 'max:100'],
            'nama_barang' => ['required', 'string', 'max:255'],
            'kategori' => ['nullable', 'string', 'max:255'],
            'satuan_default' => ['required', 'string', 'max:50'],
            'keperluan_default' => ['nullable', 'string', 'max:255'],
        ]);

        $this->masterBarang->update($validated);

        session()->flash('success', 'Barang berhasil diperbarui.');
        $this->redirect(route('master-barang.index'), navigate: true);
    }
}; ?>

<div class="max-w-xl">
    <div class="mb-6">
        <a href="{{ route('master-barang.index') }}" wire:navigate
            class="text-sm text-zinc-500 hover:text-zinc-700 inline-flex items-center gap-1 mb-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Kembali
        </a>
        <h1 class="text-2xl font-bold text-zinc-900">Edit Barang</h1>
    </div>

    <form wire:submit="simpan" class="space-y-5 bg-white p-6 rounded-xl border border-zinc-100 shadow-sm">
        <div>
            <x-input-label for="kode_barang" value="Kode Barang" />
            <x-text-input wire:model="kode_barang" id="kode_barang" class="block mt-1 w-full" type="text" />
            <x-input-error :messages="$errors->get('kode_barang')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="nama_barang" value="Nama Barang" />
            <x-text-input wire:model="nama_barang" id="nama_barang" class="block mt-1 w-full" type="text" />
            <x-input-error :messages="$errors->get('nama_barang')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="kategori" value="Kategori (opsional)" />
            <x-text-input wire:model="kategori" id="kategori" class="block mt-1 w-full" type="text" />
            <x-input-error :messages="$errors->get('kategori')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="satuan_default" value="Satuan" />
            <x-text-input wire:model="satuan_default" id="satuan_default" class="block mt-1 w-full" type="text"
                placeholder="Buah, Botol, Rim, dst" />
            <x-input-error :messages="$errors->get('satuan_default')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="keperluan_default" value="Keperluan Default (opsional)" />
            <x-text-input wire:model="keperluan_default" id="keperluan_default" class="block mt-1 w-full" type="text"
                placeholder="Auto-suggest saat isi excel transaksi nanti" />
            <x-input-error :messages="$errors->get('keperluan_default')" class="mt-2" />
        </div>

        <div class="flex items-center gap-3 pt-2">
            <x-primary-button>Simpan</x-primary-button>
            <a href="{{ route('master-barang.index') }}" wire:navigate
                class="text-sm text-zinc-500 hover:text-zinc-700">Batal</a>
        </div>
    </form>
</div>
