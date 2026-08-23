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

<div class="max-w-xl">
    <h2 class="text-lg font-semibold text-zinc-900 mb-4">Koreksi Stok</h2>

    <div class="mb-4 p-3 bg-zinc-50 rounded-md text-sm text-zinc-700">
        Gunakan fitur ini untuk mencatat selisih stok fisik (misal hasil opname, barang rusak/hilang).
        Ini bukan mengubah angka langsung, tapi menambah entri baru di riwayat ledger — supaya tetap ada jejak audit.
    </div>

    @if (session('success'))
        <div class="mb-4 p-3 bg-zinc-100 text-zinc-800 rounded-md text-sm">{{ session('success') }}</div>
    @endif

    <form wire:submit="simpan" class="space-y-4 bg-white p-6 rounded-md shadow">
        <div>
            <x-input-label for="master_barang_id" value="Barang" />
            <select wire:model="master_barang_id" id="master_barang_id"
                class="block mt-1 w-full border-gray-300 rounded-md shadow-sm focus:border-zinc-500 focus:ring-zinc-500">
                <option value="">-- Pilih Barang --</option>
                @foreach ($daftarBarang as $barang)
                    <option value="{{ $barang->id }}">{{ $barang->nama_barang }} ({{ $barang->kode_barang }})</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('master_barang_id')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="tanggal" value="Tanggal" />
            <x-text-input wire:model="tanggal" id="tanggal" class="block mt-1 w-full" type="date" />
            <x-input-error :messages="$errors->get('tanggal')" class="mt-2" />
        </div>

        <div>
            <x-input-label value="Jenis Koreksi" />
            <div class="flex gap-4 mt-1">
                <label class="flex items-center gap-2 text-sm">
                    <input type="radio" wire:model="jenis" value="tambah"> Tambah (+)
                </label>
                <label class="flex items-center gap-2 text-sm">
                    <input type="radio" wire:model="jenis" value="kurang"> Kurang (−)
                </label>
            </div>
            <x-input-error :messages="$errors->get('jenis')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="jumlah" value="Jumlah" />
            <x-text-input wire:model="jumlah" id="jumlah" class="block mt-1 w-full" type="number" min="1" />
            <x-input-error :messages="$errors->get('jumlah')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="alasan" value="Alasan (wajib diisi)" />
            <textarea wire:model="alasan" id="alasan" rows="3"
                class="block mt-1 w-full border-gray-300 rounded-md shadow-sm focus:border-zinc-500 focus:ring-zinc-500"
                placeholder="Misal: selisih stok opname fisik bulan April, barang rusak, dst"></textarea>
            <x-input-error :messages="$errors->get('alasan')" class="mt-2" />
        </div>

        <div class="flex items-center gap-3">
            <x-primary-button>Simpan Koreksi</x-primary-button>
            <a href="{{ route('persediaan.index') }}" wire:navigate
                class="text-sm text-zinc-600 hover:underline">Batal</a>
        </div>
    </form>
</div>
