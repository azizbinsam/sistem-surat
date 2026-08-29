<?php

use App\Models\Pegawai;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads;

    public Pegawai $pegawai;

    public string $nama = '';
    public string $nip = '';
    public string $jabatan = '';
    public string $kategori = '';
    public $ttd; // file baru (kalau mau ganti)

    public array $opsiKategori = [
        'kepala_sekolah' => 'Kepala Sekolah',
        'pengurus_barang_pembantu' => 'Pengurus Barang Pembantu',
        'guru' => 'Guru',
        'tendik' => 'Tendik',
    ];

    public function mount(Pegawai $pegawai): void
    {
        if ($pegawai->sekolah_id !== auth()->user()->sekolah_id) {
            abort(403);
        }

        $this->pegawai = $pegawai;
        $this->nama = $pegawai->nama;
        $this->nip = $pegawai->nip ?? '';
        $this->jabatan = $pegawai->jabatan;
        $this->kategori = $pegawai->kategori;
    }

    public function simpan(): void
    {
        $validated = $this->validate([
            'nama' => ['required', 'string', 'max:255'],
            'nip' => ['nullable', 'string', 'max:50'],
            'jabatan' => ['required', 'string', 'max:255'],
            'kategori' => ['required', 'in:kepala_sekolah,pengurus_barang_pembantu,guru,tendik'],
            'ttd' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($this->ttd) {
            // hapus file lama biar storage nggak numpuk sampah
            if ($this->pegawai->ttd_path) {
                Storage::disk('public')->delete($this->pegawai->ttd_path);
            }
            $validated['ttd_path'] = $this->ttd->store('ttd', 'public');
        }

        unset($validated['ttd']); // bukan kolom database, jangan ikut di-update mentah

        $this->pegawai->update($validated);

        session()->flash('success', 'Pegawai berhasil diperbarui.');
        $this->redirect(route('pegawai.index'), navigate: true);
    }
}; ?>

<div class="max-w-3xl">

    <div class="mb-6">
        <a href="{{ route('pegawai.index') }}" wire:navigate
            class="text-sm text-zinc-500 hover:text-zinc-700 inline-flex items-center gap-1 mb-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Kembali
        </a>

        <h1 class="text-2xl font-bold text-zinc-900">
            Edit Pegawai
        </h1>

        <p class="text-sm text-zinc-500 mt-1">
            Perbarui data pegawai dan informasi penandatangan surat.
        </p>
    </div>


    <form wire:submit="simpan" class="bg-white p-6 rounded-xl border border-zinc-100 shadow-sm">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

            {{-- Nama --}}
            <div>
                <x-input-label for="nama" value="Nama Lengkap" />

                <x-text-input wire:model="nama" id="nama" class="block mt-1 w-full" type="text" />

                <x-input-error :messages="$errors->get('nama')" class="mt-2" />
            </div>


            {{-- NIP --}}
            <div>
                <x-input-label for="nip" value="NIP (opsional)" />

                <x-text-input wire:model="nip" id="nip" class="block mt-1 w-full" type="text" />

                <x-input-error :messages="$errors->get('nip')" class="mt-2" />
            </div>


            {{-- Jabatan --}}
            <div>
                <x-input-label for="jabatan" value="Jabatan" />

                <x-text-input wire:model="jabatan" id="jabatan" class="block mt-1 w-full" type="text" />

                <x-input-error :messages="$errors->get('jabatan')" class="mt-2" />
            </div>


            {{-- Kategori --}}
            <div>
                <x-input-label for="kategori" value="Kategori" />

                <select wire:model="kategori" id="kategori"
                    class="block mt-1 w-full border-gray-300 rounded-md shadow-sm
                        focus:border-zinc-500 focus:ring-zinc-500">

                    @foreach ($opsiKategori as $value => $label)
                        <option value="{{ $value }}">
                            {{ $label }}
                        </option>
                    @endforeach

                </select>

                <x-input-error :messages="$errors->get('kategori')" class="mt-2" />

                <p class="text-xs text-zinc-500 mt-1.5">
                    Menentukan penandatangan yang muncul pada surat sesuai kategori pegawai.
                </p>
            </div>


            {{-- Tanda Tangan Saat Ini --}}
            <div class="md:col-span-2">

                <x-input-label value="Tanda Tangan Saat Ini" />

                @if ($pegawai->ttd_path)
                    <div
                        class="mt-2 inline-flex items-center justify-center
                        min-w-40 h-24 px-4 border border-zinc-200
                        rounded-lg bg-zinc-50">

                        <img src="{{ Storage::url($pegawai->ttd_path) }}" class="max-h-20 max-w-56 object-contain"
                            alt="Tanda tangan saat ini">

                    </div>
                @else
                    <div class="mt-2 flex items-center gap-2 text-sm text-zinc-500">

                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01M12 21a9 9 0 100-18 9 9 0 000 18z" />
                        </svg>

                        Belum ada tanda tangan.
                    </div>
                @endif

            </div>


            {{-- Ganti Tanda Tangan --}}
            <div class="md:col-span-2">

                <x-input-label for="ttd" value="Ganti Tanda Tangan (opsional)" />

                <p class="text-xs text-zinc-500 mt-1">
                    Biarkan kosong jika tidak ingin mengubah tanda tangan.
                </p>

                <input type="file" wire:model="ttd" id="ttd" accept="image/*"
                    class="block mt-2 w-full text-sm text-zinc-600
                        file:mr-4 file:py-2 file:px-4
                        file:rounded-lg file:border-0
                        file:text-sm file:font-medium
                        file:bg-zinc-100 file:text-zinc-700
                        hover:file:bg-zinc-200">

                <x-input-error :messages="$errors->get('ttd')" class="mt-2" />

                <div wire:loading wire:target="ttd" class="text-xs text-zinc-500 mt-1.5">
                    Mengunggah gambar...
                </div>


                {{-- Preview Tanda Tangan Baru --}}
                @if ($ttd)
                    <div class="mt-3">

                        <p class="text-xs text-zinc-500 mb-1.5">
                            Preview tanda tangan baru
                        </p>

                        <div
                            class="inline-flex items-center justify-center
                            min-w-40 h-24 px-4 border border-emerald-200
                            rounded-lg bg-emerald-50">

                            <img src="{{ $ttd->temporaryUrl() }}" class="max-h-20 max-w-56 object-contain"
                                alt="Preview tanda tangan baru">

                        </div>

                    </div>
                @endif

            </div>

        </div>


        {{-- Actions --}}
        <div class="flex items-center gap-2 mt-6 pt-5 border-t border-zinc-100">

            <x-primary-button>
                Simpan Perubahan
            </x-primary-button>

            <a href="{{ route('pegawai.index') }}" wire:navigate
                class="px-4 py-2 text-sm font-medium text-zinc-600 rounded-lg hover:bg-zinc-100">
                Batal
            </a>

        </div>

    </form>

</div>
