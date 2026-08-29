<?php

use App\Models\Sekolah;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    public string $nama_sekolah = '';
    public string $kode_sekolah = '';
    public string $nama_pemerintah = '';
    public string $nama_dinas = '';
    public string $nama_korwil = '';
    public string $alamat = '';
    public string $tempat = '';

    public function simpan(): void
    {
        $validated = $this->validate(
            [
                'nama_sekolah' => ['required', 'string', 'max:255'],
                'kode_sekolah' => ['required', 'string', 'max:50', 'unique:sekolah,kode_sekolah'],
                'nama_pemerintah' => ['required', 'string', 'max:255'],
                'nama_dinas' => ['required', 'string', 'max:255'],
                'nama_korwil' => ['nullable', 'string', 'max:255'],
                'alamat' => ['required', 'string'],
                'tempat' => ['required', 'string', 'max:100'],
            ],
            [
                'kode_sekolah.unique' => 'Kode Sekolah ini sudah terdaftar dengan akun lain. Satu sekolah cuma boleh punya satu akun — kalau ini sekolahmu, hubungi admin buat bantuan akses.',
            ],
        );

        $sekolah = Sekolah::create($validated);

        $user = Auth::user();
        $user->sekolah_id = $sekolah->id;
        $user->save();

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
};
?>

<div class="mx-auto w-full max-w-3xl">
    {{-- Intro --}}
    <div class="mb-6">
        <h1 class="text-xl font-bold text-zinc-900 sm:text-2xl">
            Lengkapi Profil Sekolah
        </h1>

        <p class="mt-1 max-w-2xl text-sm leading-6 text-zinc-500">
            Lengkapi informasi sekolah terlebih dahulu agar aplikasi dapat
            digunakan untuk pengelolaan persediaan dan pembuatan surat.
        </p>
    </div>

    {{-- Form Card --}}
    <div class="overflow-hidden rounded-xl border border-zinc-100 bg-white shadow-sm">
        <form wire:submit="simpan">

            {{-- Identitas Sekolah --}}
            <div class="border-b border-zinc-100 p-5 sm:p-6">
                <div class="mb-5">
                    <h2 class="text-sm font-semibold text-zinc-900">
                        Identitas Sekolah
                    </h2>

                    <p class="mt-1 text-xs text-zinc-500">
                        Informasi dasar mengenai sekolah yang akan digunakan dalam aplikasi.
                    </p>
                </div>

                <div class="space-y-5">
                    {{-- Nama Sekolah --}}
                    <div>
                        <x-input-label for="nama_sekolah" value="Nama Sekolah" />

                        <x-text-input wire:model="nama_sekolah" id="nama_sekolah" type="text"
                            class="mt-1.5 block w-full" placeholder="Contoh: SEKOLAH DASAR NEGERI 3 RANGKASBITUNG TIMUR"
                            autocomplete="organization" />

                        <x-input-error :messages="$errors->get('nama_sekolah')" class="mt-1.5" />
                    </div>

                    {{-- Kode Sekolah --}}
                    <div>
                        <x-input-label for="kode_sekolah" value="Kode Sekolah" />

                        <x-text-input wire:model="kode_sekolah"
                            wire:input="$set('kode_sekolah', strtoupper($event.target.value))" id="kode_sekolah"
                            type="text" class="mt-1.5 block w-full uppercase" placeholder="Contoh: SDN3RKST"
                            autocomplete="off" />

                        <p class="mt-1.5 text-xs text-zinc-500">
                            Kode unik untuk mengidentifikasi sekolah Anda.
                        </p>

                        <x-input-error :messages="$errors->get('kode_sekolah')" class="mt-1.5" />
                    </div>
                </div>
            </div>

            {{-- Instansi Pemerintahan --}}
            <div class="border-b border-zinc-100 p-5 sm:p-6">
                <div class="mb-5">
                    <h2 class="text-sm font-semibold text-zinc-900">
                        Instansi Pemerintahan
                    </h2>

                    <p class="mt-1 text-xs text-zinc-500">
                        Informasi ini digunakan pada kop dan dokumen resmi sekolah.
                    </p>
                </div>

                <div class="space-y-5">
                    {{-- Pemerintah Daerah --}}
                    <div>
                        <x-input-label for="nama_pemerintah" value="Nama Pemerintah Daerah" />

                        <x-text-input wire:model="nama_pemerintah" id="nama_pemerintah" type="text"
                            class="mt-1.5 block w-full" placeholder="Contoh: PEMERINTAH KABUPATEN LEBAK"
                            autocomplete="organization" />

                        <x-input-error :messages="$errors->get('nama_pemerintah')" class="mt-1.5" />
                    </div>

                    {{-- Dinas --}}
                    <div>
                        <x-input-label for="nama_dinas" value="Nama Dinas" />

                        <x-text-input wire:model="nama_dinas" id="nama_dinas" type="text" class="mt-1.5 block w-full"
                            placeholder="Contoh: DINAS PENDIDIKAN" autocomplete="organization" />

                        <x-input-error :messages="$errors->get('nama_dinas')" class="mt-1.5" />
                    </div>

                    {{-- Korwil --}}
                    <div>
                        <div class="flex items-center justify-between gap-3">
                            <x-input-label for="nama_korwil" value="Nama Korwil / UPTD" />

                            <span class="text-[11px] font-medium text-zinc-400">
                                Opsional
                            </span>
                        </div>

                        <x-text-input wire:model="nama_korwil" id="nama_korwil" type="text"
                            class="mt-1.5 block w-full" placeholder="Contoh: KORWIL SATUAN PENDIDIKAN"
                            autocomplete="organization" />

                        <x-input-error :messages="$errors->get('nama_korwil')" class="mt-1.5" />
                    </div>
                </div>
            </div>

            {{-- Alamat & Dokumen --}}
            <div class="p-5 sm:p-6">
                <div class="mb-5">
                    <h2 class="text-sm font-semibold text-zinc-900">
                        Alamat & Dokumen
                    </h2>

                    <p class="mt-1 text-xs text-zinc-500">
                        Informasi berikut digunakan untuk kebutuhan administrasi dan surat.
                    </p>
                </div>

                <div class="space-y-5">
                    {{-- Alamat --}}
                    <div>
                        <x-input-label for="alamat" value="Alamat Sekolah" />

                        <textarea wire:model="alamat" id="alamat" rows="3"
                            class="mt-1.5 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-zinc-500 focus:ring-zinc-500"
                            placeholder="Masukkan alamat lengkap sekolah..." autocomplete="street-address"></textarea>

                        <x-input-error :messages="$errors->get('alamat')" class="mt-1.5" />
                    </div>

                    {{-- Tempat --}}
                    <div>
                        <x-input-label for="tempat" value="Tempat" />

                        <x-text-input wire:model="tempat" id="tempat" type="text" class="mt-1.5 block w-full"
                            placeholder="Contoh: Rangkasbitung" autocomplete="address-level2" />

                        <p class="mt-1.5 text-xs text-zinc-500">
                            Digunakan sebagai tempat pada bagian tanda tangan surat.
                        </p>

                        <x-input-error :messages="$errors->get('tempat')" class="mt-1.5" />
                    </div>
                </div>
            </div>

            {{-- Footer / Submit --}}
            <div
                class="flex flex-col-reverse gap-3 border-t border-zinc-100 bg-zinc-50/70 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <p class="text-xs text-zinc-500">
                    Pastikan data yang dimasukkan sudah benar.
                </p>

                <x-primary-button type="submit" wire:loading.attr="disabled" wire:target="simpan"
                    class="justify-center">
                    <span wire:loading.remove wire:target="simpan">
                        Simpan & Lanjutkan
                    </span>

                    <span wire:loading wire:target="simpan">
                        Menyimpan...
                    </span>
                </x-primary-button>
            </div>
        </form>
    </div>
</div>
