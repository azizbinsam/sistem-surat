<?php

use App\Models\MasterBarang;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    public string $kode_barang = '';
    public string $nama_barang = '';
    public string $kategori = '';
    public string $satuan_default = '';
    public string $keperluan_default = '';
    public string $spesifikasi_default = '';

    public function simpan(): void
    {
        $validated = $this->validate([
            'kode_barang' => ['required', 'string', 'max:100'],
            'nama_barang' => ['required', 'string', 'max:255'],
            'kategori' => ['nullable', 'string', 'max:255'],
            'satuan_default' => ['required', 'string', 'max:50'],
            'keperluan_default' => ['nullable', 'string', 'max:255'],
            'spesifikasi_default' => ['nullable', 'string', 'max:255'],
        ]);

        $validated['sekolah_id'] = auth()->user()->sekolah_id;

        MasterBarang::create($validated);

        session()->flash('success', 'Barang berhasil ditambahkan.');
        $this->redirect(route('master-barang.index'), navigate: true);
    }
}; ?>

<div class="max-w-xl">
    <h2 class="text-lg font-semibold text-zinc-900 mb-4">Tambah Barang</h2>

    <form wire:submit="simpan" class="space-y-4 bg-white p-6 rounded-md shadow">
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
            <x-input-label for="spesifikasi_default" value="Spesifikasi Default (opsional)" />
            <x-text-input wire:model="spesifikasi_default" id="spesifikasi_default" class="block mt-1 w-full"
                type="text" placeholder="Auto-suggest/fallback saat isi transaksi keluar" />
            <x-input-error :messages="$errors->get('spesifikasi_default')" class="mt-2" />
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

        <div class="flex items-center gap-3">
            <x-primary-button>Simpan</x-primary-button>
            <a href="{{ route('master-barang.index') }}" wire:navigate
                class="text-sm text-zinc-600 hover:underline">Batal</a>
        </div>
    </form>
</div>
