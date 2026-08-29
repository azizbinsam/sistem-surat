<?php

use App\Models\KoreksiStok;
use App\Models\MasterBarang;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    public string $master_barang_id = '';
    public string $tanggal = '';
    public string $jenis = 'tambah'; // tambah | kurang, cuma buat UX, disimpan sebagai signed integer
    public string $jumlah = '';
    public string $alasan = '';

    public function mount(): void
    {
        $this->tanggal = now()->format('Y-m-d');
    }

    public function simpan(): void
    {
        $validated = $this->validate([
            'master_barang_id' => ['required', 'exists:master_barang,id'],
            'tanggal' => ['required', 'date'],
            'jenis' => ['required', 'in:tambah,kurang'],
            'jumlah' => ['required', 'integer', 'min:1'],
            'alasan' => ['required', 'string', 'max:500'],
        ]);

        // pastikan barang yang dipilih memang milik sekolah yang login (proteksi tenant)
        $barang = MasterBarang::where('sekolah_id', auth()->user()->sekolah_id)->findOrFail($validated['master_barang_id']);

        KoreksiStok::create([
            'sekolah_id' => auth()->user()->sekolah_id,
            'master_barang_id' => $barang->id,
            'tanggal' => $validated['tanggal'],
            'jumlah' => $validated['jenis'] === 'tambah' ? (int) $validated['jumlah'] : -(int) $validated['jumlah'],
            'alasan' => $validated['alasan'],
            'user_id' => auth()->id(),
        ]);

        session()->flash('success', 'Koreksi stok berhasil disimpan.');
        $this->redirect(route('persediaan.index'), navigate: true);
    }

    public function with(): array
    {
        return [
            'daftarBarang' => MasterBarang::where('sekolah_id', auth()->user()->sekolah_id)
                ->orderBy('nama_barang')
                ->get(),
        ];
    }
}; ?>

<div class="max-w-6xl">
    {{-- Header --}}
    <div class="mb-6">
        <a href="{{ route('persediaan.index') }}" wire:navigate
            class="text-sm text-zinc-500 hover:text-zinc-700 inline-flex items-center gap-1 mb-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Kembali
        </a>

        <h1 class="text-2xl font-bold text-zinc-900">
            Koreksi Persediaan
        </h1>

        <p class="text-sm text-zinc-500 mt-1">
            Atur persediaan dengan kondisi sebenarnya.
        </p>
    </div>

    @if (session('success'))
        <div class="mb-6 p-3 bg-zinc-100 text-zinc-800 rounded-md text-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- Content --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">

        {{-- ========================= --}}
        {{-- SIDEBAR INFORMASI --}}
        {{-- ========================= --}}
        <div class="xl:col-span-1">
            <div class="p-5 bg-amber-50 rounded-lg border border-amber-200">

                <div class="flex items-start gap-3 mb-4">
                    <div
                        class="flex-shrink-0 w-9 h-9 rounded-full bg-amber-100
                        flex items-center justify-center text-amber-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01M10.29 3.86l-7.82 13.5A2 2 0 004.2 20h15.6a2 2 0 001.73-2.64l-7.82-13.5a2 2 0 00-3.42 0z" />
                        </svg>
                    </div>

                    <div>
                        <h2 class="font-semibold text-zinc-900">
                            Kapan menggunakan koreksi?
                        </h2>

                        <p class="text-xs text-zinc-500 mt-1">
                            Gunakan fitur ini hanya untuk perubahan stok yang benar-benar terjadi secara fisik.
                        </p>
                    </div>
                </div>

                {{-- Gunakan untuk --}}
                <div class="mb-5">
                    <div class="flex items-center gap-2 mb-3">
                        <div
                            class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                        </div>

                        <h3 class="text-sm font-semibold text-zinc-800">
                            Gunakan untuk
                        </h3>
                    </div>

                    <ul class="space-y-2 text-sm text-zinc-700">
                        <li class="flex items-start gap-2">
                            <span class="text-amber-600 mt-0.5">•</span>
                            <span>Hasil opname stok berbeda dengan catatan.</span>
                        </li>

                        <li class="flex items-start gap-2">
                            <span class="text-amber-600 mt-0.5">•</span>
                            <span>Barang rusak atau tidak dapat digunakan.</span>
                        </li>

                        <li class="flex items-start gap-2">
                            <span class="text-amber-600 mt-0.5">•</span>
                            <span>Barang hilang.</span>
                        </li>

                        <li class="flex items-start gap-2">
                            <span class="text-amber-600 mt-0.5">•</span>
                            <span>Barang kadaluarsa dan harus dikeluarkan dari stok.</span>
                        </li>
                    </ul>
                </div>

                {{-- Jangan gunakan untuk --}}
                <div class="pt-4 border-t border-amber-200">
                    <div class="flex items-center gap-2 mb-3">
                        <div class="w-5 h-5 rounded-full bg-red-100 text-red-600 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </div>

                        <h3 class="text-sm font-semibold text-zinc-800">
                            Jangan gunakan untuk
                        </h3>
                    </div>


                    <p class="text-sm text-zinc-700 leading-relaxed">
                        Kesalahan administrasi seperti salah jumlah, salah tanggal,
                        atau salah input saat penerimaan maupun transaksi keluar.
                    </p>

                    <div class="mt-3 p-3 bg-white/70 rounded-md border border-amber-200">
                        <p class="text-xs text-zinc-600 leading-relaxed">
                            Untuk kesalahan input, silakan edit langsung pada
                            <a href="{{ route('barang-masuk.index') }}" wire:navigate
                                class="font-medium underline hover:text-zinc-900">
                                Penerimaan Barang
                            </a>
                            atau
                            <a href="{{ route('transaksi.index') }}" wire:navigate
                                class="font-medium underline hover:text-zinc-900">
                                Transaksi Keluar
                            </a>.
                        </p>
                    </div>
                </div>

                {{-- Audit Trail --}}
                <div class="mt-5 pt-4 border-t border-amber-200">
                    <div class="flex items-start gap-2">
                        <svg class="w-4 h-4 text-zinc-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h6l5 5v11a2 2 0 01-2 2z" />
                        </svg>

                        <p class="text-xs text-zinc-500 leading-relaxed">
                            Setiap koreksi akan menjadi
                            <strong class="text-zinc-700">entri baru di riwayat ledger</strong>,
                            sehingga perubahan stok tetap memiliki jejak audit.
                        </p>
                    </div>
                </div>

            </div>
        </div>


        {{-- ========================= --}}
        {{-- FORM --}}
        {{-- ========================= --}}
        <div class="xl:col-span-2">
            <form wire:submit="simpan" class="bg-white p-6 rounded-lg shadow">

                <div class="mb-6">
                    <h2 class="text-lg font-semibold text-zinc-900">
                        Form Koreksi
                    </h2>

                    <p class="text-sm text-zinc-500 mt-1">
                        Masukkan perubahan persediaan sesuai kondisi fisik.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                    {{-- Barang --}}
                    <div class="md:col-span-2">
                        <x-input-label for="master_barang_id" value="Barang" />

                        <select wire:model="master_barang_id" id="master_barang_id"
                            class="block mt-1 w-full border-gray-300 rounded-md shadow-sm
                                focus:border-zinc-500 focus:ring-zinc-500">

                            <option value="">-- Pilih Barang --</option>

                            @foreach ($daftarBarang as $barang)
                                <option value="{{ $barang->id }}">
                                    {{ $barang->nama_barang }}
                                    ({{ $barang->kode_barang }})
                                </option>
                            @endforeach
                        </select>

                        <x-input-error :messages="$errors->get('master_barang_id')" class="mt-2" />
                    </div>

                    {{-- Tanggal --}}
                    <div>
                        <x-input-label for="tanggal" value="Tanggal" />

                        <x-text-input wire:model="tanggal" id="tanggal" class="block mt-1 w-full" type="date" />

                        <x-input-error :messages="$errors->get('tanggal')" class="mt-2" />
                    </div>

                    {{-- Jumlah --}}
                    <div>
                        <x-input-label for="jumlah" value="Jumlah" />

                        <x-text-input wire:model="jumlah" id="jumlah" class="block mt-1 w-full" type="number"
                            min="1" />

                        <x-input-error :messages="$errors->get('jumlah')" class="mt-2" />
                    </div>

                    {{-- Jenis Koreksi --}}
                    <div class="md:col-span-2">
                        <x-input-label value="Jenis Koreksi" />

                        <div class="flex flex-wrap gap-4 mt-2">

                            <label class="flex items-center gap-2 text-sm cursor-pointer">
                                <input type="radio" wire:model="jenis" value="tambah"
                                    class="text-zinc-600 focus:ring-zinc-500">

                                <span>
                                    Tambah (+)
                                </span>
                            </label>

                            <label class="flex items-center gap-2 text-sm cursor-pointer">
                                <input type="radio" wire:model="jenis" value="kurang"
                                    class="text-zinc-600 focus:ring-zinc-500">

                                <span>
                                    Kurang (−)
                                </span>
                            </label>

                        </div>

                        <x-input-error :messages="$errors->get('jenis')" class="mt-2" />
                    </div>

                    {{-- Alasan --}}
                    <div class="md:col-span-2">
                        <x-input-label for="alasan" value="Alasan (wajib diisi)" />

                        <textarea wire:model="alasan" id="alasan" rows="4"
                            class="block mt-1 w-full border-gray-300 rounded-md shadow-sm
                                focus:border-zinc-500 focus:ring-zinc-500"
                            placeholder="Contoh: Hasil opname fisik bulan April ditemukan selisih 2 unit."></textarea>

                        <x-input-error :messages="$errors->get('alasan')" class="mt-2" />
                    </div>

                </div>

                {{-- Actions --}}
                <div class="flex items-center gap-2 mt-6 pt-5 border-t border-zinc-100">

                    <x-primary-button>
                        Simpan Koreksi
                    </x-primary-button>

                    <a href="{{ route('persediaan.index') }}" wire:navigate
                        class="px-4 py-2 text-sm font-medium text-zinc-600 rounded-lg hover:bg-zinc-100">
                        Batal
                    </a>

                </div>

            </form>
        </div>

    </div>
</div>
