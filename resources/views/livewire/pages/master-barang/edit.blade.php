<?php

use App\Models\MasterBarang;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    public MasterBarang $masterBarang;

    public string $kode_barang = '';
    public string $nama_barang = '';
    public string $kategori = '';
    public string $satuan_default = '';
    public string $keperluan_default = '';
    public string $spesifikasi_default = '';

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
        $this->spesifikasi_default = $masterBarang->spesifikasi_default ?? '';
    }

    public function simpan(): void
    {
        $validated = $this->validate(
            [
                'kode_barang' => [
                    'required',
                    'string',
                    'max:100',
                    Rule::unique('master_barang', 'kode_barang')
                        ->where('sekolah_id', auth()->user()->sekolah_id)
                        ->whereNull('deleted_at')
                        ->ignore($this->masterBarang->id),
                ],
                'nama_barang' => ['required', 'string', 'max:255'],
                'kategori' => ['nullable', 'string', 'max:255'],
                'satuan_default' => ['required', 'string', 'max:50'],
                'keperluan_default' => ['nullable', 'string', 'max:255'],
                'spesifikasi_default' => ['nullable', 'string', 'max:255'],
            ],
            [
                'kode_barang.unique' => 'Kode barang ini sudah dipakai barang lain di sekolahmu.',
            ],
        );

        $this->masterBarang->update($validated);

        session()->flash('success', 'Barang berhasil diperbarui.');
        $this->redirect(route('master-barang.index'), navigate: true);
    }
}; ?>

<div class="max-w-3xl">

    <div class="mb-6">
        <a href="{{ route('master-barang.index') }}" wire:navigate
            class="text-sm text-zinc-500 hover:text-zinc-700 inline-flex items-center gap-1 mb-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Kembali
        </a>

        <h1 class="text-2xl font-bold text-zinc-900">
            Edit Barang
        </h1>

        <p class="text-sm text-zinc-500 mt-1">
            Perbarui informasi barang pada master barang.
        </p>
    </div>


    <form wire:submit="simpan" class="bg-white p-6 rounded-xl border border-zinc-100 shadow-sm">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

            {{-- Kode Barang --}}
            <div>
                <x-input-label for="kode_barang" value="Kode Barang" />

                <x-text-input wire:model="kode_barang" id="kode_barang" class="block mt-1 w-full" type="text" />

                <x-input-error :messages="$errors->get('kode_barang')" class="mt-2" />
            </div>


            {{-- Nama Barang --}}
            <div>
                <x-input-label for="nama_barang" value="Nama Barang" />

                <x-text-input wire:model="nama_barang" id="nama_barang" class="block mt-1 w-full" type="text" />

                <x-input-error :messages="$errors->get('nama_barang')" class="mt-2" />
            </div>


            {{-- Satuan --}}
            <div>
                <x-input-label for="satuan_default" value="Satuan" />

                <x-text-input wire:model="satuan_default" id="satuan_default" class="block mt-1 w-full" type="text"
                    placeholder="Buah, Botol, Rim, dst" />

                <x-input-error :messages="$errors->get('satuan_default')" class="mt-2" />
            </div>


            {{-- Kategori --}}
            <div>
                <x-input-label for="kategori" value="Kategori (opsional)" />

                <x-text-input wire:model="kategori" id="kategori" class="block mt-1 w-full" type="text" />

                <x-input-error :messages="$errors->get('kategori')" class="mt-2" />
            </div>


            {{-- Spesifikasi --}}
            <div>
                <x-input-label for="spesifikasi_default" value="Spesifikasi Default (opsional)" />

                <x-text-input wire:model="spesifikasi_default" id="spesifikasi_default" class="block mt-1 w-full"
                    type="text" placeholder="Contoh: A4 70gsm" />

                <x-input-error :messages="$errors->get('spesifikasi_default')" class="mt-2" />
            </div>


            {{-- Keperluan --}}
            <div>
                <x-input-label for="keperluan_default" value="Keperluan Default (opsional)" />

                <x-text-input wire:model="keperluan_default" id="keperluan_default" class="block mt-1 w-full"
                    type="text" placeholder="Contoh: Administrasi" />

                <x-input-error :messages="$errors->get('keperluan_default')" class="mt-2" />
            </div>

        </div>


        {{-- Actions --}}
        <div class="flex items-center gap-2 mt-6 pt-5 border-t border-zinc-100">

            <x-primary-button>
                Simpan Perubahan
            </x-primary-button>

            <a href="{{ route('master-barang.index') }}" wire:navigate
                class="px-4 py-2 text-sm font-medium text-zinc-600 rounded-lg hover:bg-zinc-100">
                Batal
            </a>

        </div>

    </form>

</div>
