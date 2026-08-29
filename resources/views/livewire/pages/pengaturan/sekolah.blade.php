<?php

use App\Models\Sekolah;
use App\Models\TahunAnggaran;
use App\Services\TahunAnggaranResolver;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads;

    public Sekolah $sekolah;
    public TahunAnggaran $tahunAnggaranAktif;

    public string $nama_sekolah = '';
    public string $kode_sekolah = '';
    public string $nama_pemerintah = '';
    public string $nama_dinas = '';
    public string $nama_korwil = '';
    public string $alamat = '';
    public string $tempat = '';
    public string $kontak_wa = '';
    public string $email = '';
    public string $jabatan_resmi_sppb = '';
    public string $kode_klasifikasi_surat = '';
    public int $nomor_urut_terakhir = 0;

    public $logo_sekolah_baru;
    public $logo_kabupaten_baru;

    public string $password_baru = '';
    public string $password_baru_confirmation = '';

    public function mount(TahunAnggaranResolver $resolver): void
    {
        $this->sekolah = auth()->user()->sekolah;
        $this->tahunAnggaranAktif = $resolver->aktif($this->sekolah);

        $this->nama_sekolah = $this->sekolah->nama_sekolah;
        $this->kode_sekolah = $this->sekolah->kode_sekolah;
        $this->nama_pemerintah = $this->sekolah->nama_pemerintah;
        $this->nama_dinas = $this->sekolah->nama_dinas;
        $this->nama_korwil = $this->sekolah->nama_korwil ?? '';
        $this->alamat = $this->sekolah->alamat;
        $this->tempat = $this->sekolah->tempat;
        $this->kontak_wa = $this->sekolah->kontak_wa ?? '';
        $this->email = $this->sekolah->email ?? '';
        $this->jabatan_resmi_sppb = $this->sekolah->jabatan_resmi_sppb;
        $this->kode_klasifikasi_surat = $this->sekolah->kode_klasifikasi_surat;
        $this->nomor_urut_terakhir = $this->tahunAnggaranAktif->nomor_urut_terakhir;
    }

    public function simpanProfil(): void
    {
        $validated = $this->validate(
            [
                'nama_sekolah' => ['required', 'string', 'max:255'],
                'kode_sekolah' => ['required', 'string', 'max:50', Rule::unique('sekolah', 'kode_sekolah')->ignore($this->sekolah->id)],
                'nama_pemerintah' => ['required', 'string', 'max:255'],
                'nama_dinas' => ['required', 'string', 'max:255'],
                'nama_korwil' => ['nullable', 'string', 'max:255'],
                'alamat' => ['required', 'string'],
                'tempat' => ['required', 'string', 'max:100'],
                'kontak_wa' => ['nullable', 'string', 'max:30'],
                'email' => ['nullable', 'email', 'max:255'],
                'jabatan_resmi_sppb' => ['required', 'string', 'max:255'],
                'kode_klasifikasi_surat' => ['required', 'string', 'max:50'],
                'nomor_urut_terakhir' => ['required', 'integer', 'min:0'],
                'logo_sekolah_baru' => ['nullable', 'image', 'max:2048'],
                'logo_kabupaten_baru' => ['nullable', 'image', 'max:2048'],
            ],
            [
                'kode_sekolah.unique' => 'Kode Sekolah ini sudah dipakai akun lain. Satu sekolah cuma boleh punya satu akun.',
            ],
        );

        if ($this->logo_sekolah_baru) {
            if ($this->sekolah->logo_sekolah) {
                Storage::disk('public')->delete($this->sekolah->logo_sekolah);
            }
            $validated['logo_sekolah'] = $this->logo_sekolah_baru->store('logo', 'public');
        }

        if ($this->logo_kabupaten_baru) {
            if ($this->sekolah->logo_kabupaten) {
                Storage::disk('public')->delete($this->sekolah->logo_kabupaten);
            }
            $validated['logo_kabupaten'] = $this->logo_kabupaten_baru->store('logo', 'public');
        }

        unset($validated['logo_sekolah_baru'], $validated['logo_kabupaten_baru']);

        // nomor_urut_terakhir sekarang milik Tahun Anggaran aktif (PRD §12.3), bukan Sekolah
        $nomorUrutBaru = $validated['nomor_urut_terakhir'];
        unset($validated['nomor_urut_terakhir']);

        $this->sekolah->update($validated);
        $this->tahunAnggaranAktif->update(['nomor_urut_terakhir' => $nomorUrutBaru]);

        session()->flash('success_profil', 'Profil sekolah berhasil diperbarui.');
        $this->logo_sekolah_baru = null;
        $this->logo_kabupaten_baru = null;
    }

    public function gantiPassword(): void
    {
        $this->validate([
            'password_baru' => ['required', 'confirmed', 'min:8'],
        ]);

        Auth::user()->update([
            'password' => Hash::make($this->password_baru),
        ]);

        $this->reset(['password_baru', 'password_baru_confirmation']);
        session()->flash('success_password', 'Password berhasil diubah.');
    }
}; ?>

<div x-data="{ activeSection: 'identitas' }" class="max-w-7xl">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-zinc-900">Pengaturan Sekolah</h1>
        <p class="text-zinc-500 text-sm mt-1">Kelola identitas, kop surat, dan penomoran dokumen sekolah kamu.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        {{-- Sidebar navigasi section (desktop) --}}
        <aside class="hidden lg:block lg:col-span-1">
            <nav class="sticky top-20 space-y-1 bg-white rounded-xl border border-zinc-100 shadow-sm p-2">
                <a href="#identitas" @click="activeSection = 'identitas'"
                    class="block px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                    :class="activeSection === 'identitas' ? 'bg-emerald-50 text-emerald-700' : 'text-zinc-600 hover:bg-zinc-50'">
                    Identitas Sekolah
                </a>
                <a href="#kop-surat" @click="activeSection = 'kop-surat'"
                    class="block px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                    :class="activeSection === 'kop-surat' ? 'bg-emerald-50 text-emerald-700' : 'text-zinc-600 hover:bg-zinc-50'">
                    Logo & Kop Surat
                </a>
                <a href="#penomoran" @click="activeSection = 'penomoran'"
                    class="block px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                    :class="activeSection === 'penomoran' ? 'bg-emerald-50 text-emerald-700' : 'text-zinc-600 hover:bg-zinc-50'">
                    Penomoran Surat
                </a>
                <a href="#keamanan" @click="activeSection = 'keamanan'"
                    class="block px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                    :class="activeSection === 'keamanan' ? 'bg-emerald-50 text-emerald-700' : 'text-zinc-600 hover:bg-zinc-50'">
                    Keamanan
                </a>
            </nav>
        </aside>

        {{-- Konten utama --}}
        <div class="lg:col-span-3 space-y-6">
            @if (session('success_profil'))
                <div x-data="{ show: true }" x-show="show" x-transition
                    class="flex items-center justify-between gap-3 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-sm">
                    <span class="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" class="shrink-0">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                        {{ session('success_profil') }}
                    </span>
                    <button @click="show = false" type="button"
                        class="text-emerald-600 hover:text-emerald-800">✕</button>
                </div>
            @endif

            <form wire:submit="simpanProfil" class="space-y-6">
                {{-- Identitas Sekolah --}}
                <section id="identitas" class="bg-white rounded-xl border border-zinc-100 shadow-sm p-6 scroll-mt-6">
                    <h2 class="text-base font-semibold text-zinc-900">Identitas Sekolah</h2>
                    <p class="text-xs text-zinc-500 mt-0.5 mb-5">Data ini dipakai di kop surat dan dokumen resmi yang
                        digenerate.</p>

                    <div class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="nama_sekolah" value="Nama Sekolah" />
                                <x-text-input wire:model="nama_sekolah" id="nama_sekolah" class="block mt-1 w-full"
                                    type="text" />
                                <x-input-error :messages="$errors->get('nama_sekolah')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="kode_sekolah" value="Kode Sekolah" />
                                <x-text-input wire:model="kode_sekolah" id="kode_sekolah" class="block mt-1 w-full"
                                    type="text" />
                                <x-input-error :messages="$errors->get('kode_sekolah')" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="nama_pemerintah" value="Nama Pemerintah Daerah" />
                                <x-text-input wire:model="nama_pemerintah" id="nama_pemerintah"
                                    class="block mt-1 w-full" type="text" />
                                <x-input-error :messages="$errors->get('nama_pemerintah')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="nama_dinas" value="Nama Dinas" />
                                <x-text-input wire:model="nama_dinas" id="nama_dinas" class="block mt-1 w-full"
                                    type="text" />
                                <x-input-error :messages="$errors->get('nama_dinas')" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="nama_korwil" value="Nama Korwil/UPTD (opsional)" />
                            <x-text-input wire:model="nama_korwil" id="nama_korwil" class="block mt-1 w-full"
                                type="text" />
                            <x-input-error :messages="$errors->get('nama_korwil')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="alamat" value="Alamat Sekolah" />
                            <textarea wire:model="alamat" id="alamat" rows="3"
                                class="block mt-1 w-full border-zinc-300 rounded-md text-sm"></textarea>
                            <x-input-error :messages="$errors->get('alamat')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="tempat" value="Tempat (dipakai di tanda tangan surat)" />
                            <x-text-input wire:model="tempat" id="tempat" class="block mt-1 w-full" type="text" />
                            <x-input-error :messages="$errors->get('tempat')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="kontak_wa" value="Kontak WA" />
                                <x-text-input wire:model="kontak_wa" id="kontak_wa" class="block mt-1 w-full"
                                    type="text" />
                                <x-input-error :messages="$errors->get('kontak_wa')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="email" value="Email" />
                                <x-text-input wire:model="email" id="email" class="block mt-1 w-full"
                                    type="email" />
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Logo & Kop Surat --}}
                <section id="kop-surat" class="bg-white rounded-xl border border-zinc-100 shadow-sm p-6 scroll-mt-6">
                    <h2 class="text-base font-semibold text-zinc-900">Logo & Kop Surat</h2>
                    <p class="text-xs text-zinc-500 mt-0.5 mb-5">Format gambar (JPG/PNG), maksimal 2MB per file.</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- Logo Sekolah --}}
                        <div>
                            <x-input-label value="Logo Sekolah" />
                            <label for="logo_sekolah_baru"
                                class="mt-1 flex flex-col items-center justify-center gap-2 border-2 border-dashed border-zinc-200 rounded-lg p-4 cursor-pointer hover:border-emerald-300 hover:bg-emerald-50/30 transition-colors">
                                @if ($logo_sekolah_baru)
                                    <img src="{{ $logo_sekolah_baru->temporaryUrl() }}" class="h-20 object-contain">
                                    <span class="text-xs text-emerald-700 font-medium">Logo baru — klik untuk
                                        ganti</span>
                                @elseif ($sekolah->logo_sekolah)
                                    <img src="{{ Storage::url($sekolah->logo_sekolah) }}" class="h-20 object-contain">
                                    <span class="text-xs text-zinc-500">Klik untuk ganti logo</span>
                                @else
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                                        stroke-linecap="round" stroke-linejoin="round" class="text-zinc-400">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <polyline points="17 8 12 3 7 8"></polyline>
                                        <line x1="12" y1="3" x2="12" y2="15"></line>
                                    </svg>
                                    <span class="text-xs text-zinc-500">Klik untuk upload logo sekolah</span>
                                @endif
                                <input id="logo_sekolah_baru" type="file" wire:model="logo_sekolah_baru"
                                    accept="image/*" class="hidden">
                            </label>
                            <div wire:loading wire:target="logo_sekolah_baru" class="text-xs text-zinc-400 mt-1">
                                Mengunggah...</div>
                            <x-input-error :messages="$errors->get('logo_sekolah_baru')" class="mt-1" />
                        </div>

                        {{-- Logo Kabupaten --}}
                        <div>
                            <x-input-label value="Logo Kabupaten" />
                            <label for="logo_kabupaten_baru"
                                class="mt-1 flex flex-col items-center justify-center gap-2 border-2 border-dashed border-zinc-200 rounded-lg p-4 cursor-pointer hover:border-emerald-300 hover:bg-emerald-50/30 transition-colors">
                                @if ($logo_kabupaten_baru)
                                    <img src="{{ $logo_kabupaten_baru->temporaryUrl() }}" class="h-20 object-contain">
                                    <span class="text-xs text-emerald-700 font-medium">Logo baru — klik untuk
                                        ganti</span>
                                @elseif ($sekolah->logo_kabupaten)
                                    <img src="{{ Storage::url($sekolah->logo_kabupaten) }}"
                                        class="h-20 object-contain">
                                    <span class="text-xs text-zinc-500">Klik untuk ganti logo</span>
                                @else
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                                        stroke-linecap="round" stroke-linejoin="round" class="text-zinc-400">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <polyline points="17 8 12 3 7 8"></polyline>
                                        <line x1="12" y1="3" x2="12" y2="15"></line>
                                    </svg>
                                    <span class="text-xs text-zinc-500">Klik untuk upload logo kabupaten</span>
                                @endif
                                <input id="logo_kabupaten_baru" type="file" wire:model="logo_kabupaten_baru"
                                    accept="image/*" class="hidden">
                            </label>
                            <div wire:loading wire:target="logo_kabupaten_baru" class="text-xs text-zinc-400 mt-1">
                                Mengunggah...</div>
                            <x-input-error :messages="$errors->get('logo_kabupaten_baru')" class="mt-1" />
                        </div>
                    </div>
                </section>

                {{-- Penomoran Surat --}}
                <section id="penomoran" class="bg-white rounded-xl border border-zinc-100 shadow-sm p-6 scroll-mt-6">
                    <h2 class="text-base font-semibold text-zinc-900">Penomoran Surat</h2>
                    <p class="text-xs text-zinc-500 mt-0.5 mb-5">Pengaturan penandatangan dan urutan nomor dokumen.</p>

                    <div class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="jabatan_resmi_sppb" value="Jabatan Resmi Penandatangan SPPB" />
                                <x-text-input wire:model="jabatan_resmi_sppb" id="jabatan_resmi_sppb"
                                    class="block mt-1 w-full" type="text" />
                                <x-input-error :messages="$errors->get('jabatan_resmi_sppb')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="kode_klasifikasi_surat" value="Kode Klasifikasi Surat" />
                                <x-text-input wire:model="kode_klasifikasi_surat" id="kode_klasifikasi_surat"
                                    class="block mt-1 w-full" type="text" />
                                <x-input-error :messages="$errors->get('kode_klasifikasi_surat')" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="nomor_urut_terakhir" value="Nomor Urut Terakhir" />
                            <x-text-input wire:model="nomor_urut_terakhir" id="nomor_urut_terakhir"
                                class="block mt-1 w-full sm:w-48" type="number" min="0" />
                            <x-input-error :messages="$errors->get('nomor_urut_terakhir')" class="mt-2" />

                            <div class="mt-3 flex gap-2.5 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round"
                                    class="text-amber-600 shrink-0 mt-0.5">
                                    <path
                                        d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z">
                                    </path>
                                    <line x1="12" y1="9" x2="12" y2="13"></line>
                                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                                </svg>
                                <p class="text-xs text-amber-800">
                                    Nomor NPB berikutnya akan mulai dari angka ini + 1 — khusus tahun anggaran
                                    <strong>{{ $tahunAnggaranAktif->tahun }}</strong> yang lagi aktif. Ubah cuma buat
                                    lanjutin urutan dari pencatatan kertas lama — salah isi bisa bikin nomor surat
                                    baru bentrok/melompat.
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <div class="flex justify-end">
                    <x-primary-button>Simpan Perubahan</x-primary-button>
                </div>
            </form>

            {{-- Keamanan --}}
            <section id="keamanan" class="bg-white rounded-xl border border-zinc-100 shadow-sm p-6 scroll-mt-6">
                <h2 class="text-base font-semibold text-zinc-900">Keamanan</h2>
                <p class="text-xs text-zinc-500 mt-0.5 mb-5">Ubah password akun kamu secara berkala.</p>

                @if (session('success_password'))
                    <div x-data="{ show: true }" x-show="show" x-transition
                        class="flex items-center justify-between gap-3 mb-4 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-sm">
                        <span class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" class="shrink-0">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                            {{ session('success_password') }}
                        </span>
                        <button @click="show = false" type="button"
                            class="text-emerald-600 hover:text-emerald-800">✕</button>
                    </div>
                @endif

                <form wire:submit="gantiPassword" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="password_baru" value="Password Baru" />
                            <x-text-input wire:model="password_baru" id="password_baru" class="block mt-1 w-full"
                                type="password" />
                            <x-input-error :messages="$errors->get('password_baru')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="password_baru_confirmation" value="Konfirmasi Password Baru" />
                            <x-text-input wire:model="password_baru_confirmation" id="password_baru_confirmation"
                                class="block mt-1 w-full" type="password" />
                        </div>
                    </div>
                    <p class="text-xs text-zinc-500">Minimal 8 karakter.</p>

                    <div class="flex justify-end">
                        <x-primary-button>Ubah Password</x-primary-button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</div>
