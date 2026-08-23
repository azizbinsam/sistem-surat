<?php

use App\Models\Sekolah;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public string $nama_sekolah = '';
    public string $kode_sekolah = '';
    public string $nama_pemerintah = '';
    public string $nama_dinas = '';
    public string $nama_korwil = '';
    public string $alamat = '';
    public string $tempat = '';

    public function simpan(): void
    {
        $validated = $this->validate([
            'nama_sekolah' => ['required', 'string', 'max:255'],
            'kode_sekolah' => ['required', 'string', 'max:50'],
            'nama_pemerintah' => ['required', 'string', 'max:255'],
            'nama_dinas' => ['required', 'string', 'max:255'],
            'nama_korwil' => ['nullable', 'string', 'max:255'],
            'alamat' => ['required', 'string'],
            'tempat' => ['required', 'string', 'max:100'],
        ]);

        $sekolah = Sekolah::create($validated);

        $user = Auth::user();
        $user->sekolah_id = $sekolah->id;
        $user->save();

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div class="max-w-2xl mx-auto">
    <h2 class="text-lg font-semibold mb-4">Lengkapi Profil Sekolah</h2>
    <form wire:submit="simpan" class="space-y-4">
        <div>
            <x-input-label for="nama_sekolah" value="Nama Sekolah" />
            <x-text-input wire:model="nama_sekolah" id="nama_sekolah" class="block mt-1 w-full" type="text" />
            <x-input-error :messages="$errors->get('nama_sekolah')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="kode_sekolah" value="Kode Sekolah (contoh: SDN3RKST)" />
            <x-text-input wire:model="kode_sekolah" id="kode_sekolah" class="block mt-1 w-full" type="text" />
            <x-input-error :messages="$errors->get('kode_sekolah')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="nama_pemerintah" value="Nama Pemerintah Daerah (contoh: PEMERINTAH KABUPATEN LEBAK)" />
            <x-text-input wire:model="nama_pemerintah" id="nama_pemerintah" class="block mt-1 w-full" type="text" />
            <x-input-error :messages="$errors->get('nama_pemerintah')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="nama_dinas" value="Nama Dinas (contoh: DINAS PENDIDIKAN)" />
            <x-text-input wire:model="nama_dinas" id="nama_dinas" class="block mt-1 w-full" type="text" />
            <x-input-error :messages="$errors->get('nama_dinas')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="nama_korwil" value="Nama Korwil/UPTD (opsional)" />
            <x-text-input wire:model="nama_korwil" id="nama_korwil" class="block mt-1 w-full" type="text" />
            <x-input-error :messages="$errors->get('nama_korwil')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="alamat" value="Alamat Sekolah" />
            <textarea wire:model="alamat" id="alamat" class="block mt-1 w-full border-gray-300 rounded-md"></textarea>
            <x-input-error :messages="$errors->get('alamat')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="tempat" value="Tempat (dipakai di tanda tangan surat, contoh: Rangkasbitung)" />
            <x-text-input wire:model="tempat" id="tempat" class="block mt-1 w-full" type="text" />
            <x-input-error :messages="$errors->get('tempat')" class="mt-2" />
        </div>

        <x-primary-button>Simpan & Lanjutkan</x-primary-button>
    </form>
</div>