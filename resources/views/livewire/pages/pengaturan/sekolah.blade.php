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

<div class="max-w-2xl space-y-8">
    <div>
        <h2 class="text-lg font-semibold text-zinc-900 mb-4">Identitas & Kop Surat</h2>

        @if (session('success_profil'))
            <div class="mb-4 p-3 bg-zinc-100 text-zinc-800 rounded-md text-sm">{{ session('success_profil') }}</div>
        @endif

        <form wire:submit="simpanProfil" class="space-y-4 bg-white p-6 rounded-md shadow">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-input-label value="Logo Sekolah" />
                    @if ($sekolah->logo_sekolah)
                        <img src="{{ Storage::url($sekolah->logo_sekolah) }}" class="h-16 my-2">
                    @endif
                    <input type="file" wire:model="logo_sekolah_baru" accept="image/*" class="block w-full text-sm">
                    <x-input-error :messages="$errors->get('logo_sekolah_baru')" class="mt-1" />
                </div>
                <div>
                    <x-input-label value="Logo Kabupaten" />
                    @if ($sekolah->logo_kabupaten)
                        <img src="{{ Storage::url($sekolah->logo_kabupaten) }}" class="h-16 my-2">
                    @endif
                    <input type="file" wire:model="logo_kabupaten_baru" accept="image/*"
                        class="block w-full text-sm">
                    <x-input-error :messages="$errors->get('logo_kabupaten_baru')" class="mt-1" />
                </div>
            </div>

            <div>
                <x-input-label for="nama_sekolah" value="Nama Sekolah" />
                <x-text-input wire:model="nama_sekolah" id="nama_sekolah" class="block mt-1 w-full" type="text" />
                <x-input-error :messages="$errors->get('nama_sekolah')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="kode_sekolah" value="Kode Sekolah" />
                <x-text-input wire:model="kode_sekolah" id="kode_sekolah" class="block mt-1 w-full" type="text" />
                <x-input-error :messages="$errors->get('kode_sekolah')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="nama_pemerintah" value="Nama Pemerintah Daerah" />
                <x-text-input wire:model="nama_pemerintah" id="nama_pemerintah" class="block mt-1 w-full"
                    type="text" />
                <x-input-error :messages="$errors->get('nama_pemerintah')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="nama_dinas" value="Nama Dinas" />
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
                <x-input-label for="tempat" value="Tempat (dipakai di tanda tangan surat)" />
                <x-text-input wire:model="tempat" id="tempat" class="block mt-1 w-full" type="text" />
                <x-input-error :messages="$errors->get('tempat')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="kontak_wa" value="Kontak WA" />
                <x-text-input wire:model="kontak_wa" id="kontak_wa" class="block mt-1 w-full" type="text" />
            </div>

            <div>
                <x-input-label for="email" value="Email" />
                <x-text-input wire:model="email" id="email" class="block mt-1 w-full" type="email" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="jabatan_resmi_sppb" value="Jabatan Resmi Penandatangan SPPB" />
                <x-text-input wire:model="jabatan_resmi_sppb" id="jabatan_resmi_sppb" class="block mt-1 w-full"
                    type="text" />
                <x-input-error :messages="$errors->get('jabatan_resmi_sppb')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="kode_klasifikasi_surat" value="Kode Klasifikasi Surat" />
                <x-text-input wire:model="kode_klasifikasi_surat" id="kode_klasifikasi_surat" class="block mt-1 w-full"
                    type="text" />
                <x-input-error :messages="$errors->get('kode_klasifikasi_surat')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="nomor_urut_terakhir" value="Nomor Urut Terakhir" />
                <x-text-input wire:model="nomor_urut_terakhir" id="nomor_urut_terakhir" class="block mt-1 w-full"
                    type="number" min="0" />
                <p class="mt-1 text-xs text-amber-700">
                    ⚠ Nomor NPB berikutnya akan mulai dari angka ini + 1 — khusus tahun anggaran
                    <strong>{{ $tahunAnggaranAktif->tahun }}</strong> yang lagi aktif. Ubah cuma buat lanjutin urutan
                    dari pencatatan kertas lama — salah isi bisa bikin nomor surat baru bentrok/melompat.
                </p>
                <x-input-error :messages="$errors->get('nomor_urut_terakhir')" class="mt-2" />
            </div>

            <x-primary-button>Simpan Perubahan</x-primary-button>
        </form>
    </div>

    <div>
        <h2 class="text-lg font-semibold text-zinc-900 mb-4">Ubah Password</h2>

        @if (session('success_password'))
            <div class="mb-4 p-3 bg-zinc-100 text-zinc-800 rounded-md text-sm">{{ session('success_password') }}</div>
        @endif

        <form wire:submit="gantiPassword" class="space-y-4 bg-white p-6 rounded-md shadow">
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

            <x-primary-button>Ubah Password</x-primary-button>
        </form>
    </div>
</div>
