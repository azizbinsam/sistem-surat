<?php

use App\Models\MasterBarang;
use App\Models\Pegawai;
use App\Models\Transaksi;
use App\Models\TransaksiItem;
use App\Services\PersediaanService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    public Transaksi $transaksi;

    public string $nomor_referensi_asal = '';
    public string $tanggal_npb = '';
    public $pihak_peminta_id = '';
    public array $items = [];

    public bool $showWarning = false;
    public string $warningMessage = '';

    public function mount(Transaksi $transaksi): void
    {
        if ($transaksi->sekolah_id !== auth()->user()->sekolah_id) {
            abort(403);
        }

        $this->transaksi = $transaksi;
        $this->nomor_referensi_asal = $transaksi->nomor_referensi_asal;
        $this->tanggal_npb = $transaksi->tanggal_npb->format('Y-m-d');
        $this->pihak_peminta_id = $transaksi->pihak_peminta_id;

        foreach ($transaksi->items as $item) {
            $this->items[] = [
                'master_barang_id' => $item->master_barang_id,
                'spesifikasi' => $item->spesifikasi,
                'satuan' => $item->satuan,
                'jumlah' => $item->jumlah,
                'keperluan' => $item->keperluan,
            ];
        }
    }

    public function tambahBaris(): void
    {
        $this->items[] = ['master_barang_id' => '', 'spesifikasi' => '', 'satuan' => '', 'jumlah' => '', 'keperluan' => ''];
    }

    public function hapusBaris(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
        $this->showWarning = false;
    }

    public function updated($name, $value): void
    {
        if (preg_match('/^items\.(\d+)\.master_barang_id$/', $name, $m)) {
            $index = (int) $m[1];
            $barang = MasterBarang::find($value);
            if ($barang) {
                $this->items[$index]['satuan'] = $barang->satuan_default;
                if (blank($this->items[$index]['spesifikasi'])) {
                    $spesifikasiTerakhir = app(\App\Services\BarangMatcher::class)->cariSpesifikasiTerakhir($value, \Carbon\Carbon::parse($this->tanggal_npb));
                    $this->items[$index]['spesifikasi'] = $spesifikasiTerakhir ?? ($barang->spesifikasi_default ?? '');
                }
            }
        }

        if (str($name)->startsWith('items.') || $name === 'pihak_peminta_id') {
            $this->showWarning = false;
        }
    }

    public function simpan(PersediaanService $service): void
    {
        $sekolahId = auth()->user()->sekolah_id;

        $validated = $this->validate([
            'nomor_referensi_asal' => ['required', 'string', 'max:100', Rule::unique('transaksi', 'nomor_referensi_asal')->where('sekolah_id', $sekolahId)->ignore($this->transaksi->id)],
            'tanggal_npb' => ['required', 'date'],
            'pihak_peminta_id' => ['required', 'exists:pegawai,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.master_barang_id' => ['required', 'exists:master_barang,id'],
            'items.*.spesifikasi' => ['nullable', 'string', 'max:255'],
            'items.*.satuan' => ['required', 'string', 'max:50'],
            'items.*.jumlah' => ['required', 'integer', 'min:1'],
            'items.*.keperluan' => ['required', 'string', 'max:255'],
        ]);

        if (!$this->showWarning) {
            $originalQty = $this->transaksi->items->groupBy('master_barang_id')->map(fn($g) => $g->sum('jumlah'));
            $newQty = collect($validated['items'])->groupBy('master_barang_id')->map(fn($g) => collect($g)->sum('jumlah'));
            $semuaBarangId = $originalQty->keys()->merge($newQty->keys())->unique();

            $peringatan = [];
            foreach ($semuaBarangId as $barangId) {
                $old = $originalQty->get($barangId, 0);
                $new = $newQty->get($barangId, 0);
                $deltaTambahKeluar = $new - $old; // positif = butuh lebih banyak stok dari sebelumnya

                if ($deltaTambahKeluar > 0) {
                    $sisaSaatIni = $service->sisaSaatIni($barangId);
                    if ($deltaTambahKeluar > $sisaSaatIni) {
                        $barang = MasterBarang::find($barangId);
                        $peringatan[] = "{$barang->nama_barang} (sisa saat ini {$sisaSaatIni}, butuh tambahan {$deltaTambahKeluar})";
                    }
                }
            }

            if (!empty($peringatan)) {
                $this->warningMessage = 'Stok tidak cukup: ' . implode(', ', $peringatan) . '.';
                $this->showWarning = true;
                return;
            }
        }

        DB::transaction(function () use ($validated) {
            $this->transaksi->update([
                'nomor_referensi_asal' => $validated['nomor_referensi_asal'],
                'tanggal_npb' => $validated['tanggal_npb'],
                'pihak_peminta_id' => $validated['pihak_peminta_id'],
                // nomor_npb/spb/sppb SENGAJA tidak diubah - kalau udah pernah terbit, nomornya tetap
            ]);

            $this->transaksi->items()->delete();

            foreach ($validated['items'] as $item) {
                $barang = MasterBarang::find($item['master_barang_id']);

                TransaksiItem::create([
                    'transaksi_id' => $this->transaksi->id,
                    'master_barang_id' => $item['master_barang_id'],
                    'spesifikasi' => $item['spesifikasi'] ?: $barang->spesifikasi_default ?? $barang->nama_barang,
                    'jumlah' => $item['jumlah'],
                    'satuan' => $item['satuan'],
                    'keperluan' => $item['keperluan'],
                ]);
            }
        });

        $pesan = 'Transaksi berhasil diperbarui.';
        if ($this->transaksi->status === 'selesai') {
            $pesan .= ' Nomor surat tidak berubah — jangan lupa download ulang biar datanya sinkron.';
        }

        session()->flash('success', $pesan);
        $this->redirect(route('transaksi.index'), navigate: true);
    }

    public function with(): array
    {
        return [
            'daftarMasterBarang' => MasterBarang::where('sekolah_id', auth()->user()->sekolah_id)
                ->orderBy('nama_barang')
                ->get(),
            'daftarPegawai' => Pegawai::where('sekolah_id', auth()->user()->sekolah_id)
                ->orderBy('nama')
                ->get(),
        ];
    }
}; ?>

<div class="max-w-4xl">
    <div class="mb-6">
        <a href="{{ route('transaksi.index') }}" wire:navigate
            class="text-sm text-zinc-500 hover:text-zinc-700 inline-flex items-center gap-1 mb-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Kembali
        </a>
        <h1 class="text-2xl font-bold text-zinc-900">Edit Transaksi</h1>
    </div>

    @if ($transaksi->status === 'selesai')
        <div class="mb-4 p-3 bg-zinc-100 text-zinc-700 rounded-lg text-sm">
            Surat ini udah pernah digenerate dengan nomor <strong>{{ $transaksi->nomor_npb }}</strong>. Nomor itu nggak
            berubah walau kamu edit data di sini — jangan lupa download ulang suratnya setelah simpan biar isinya
            sinkron.
        </div>
    @endif

    @if ($showWarning)
        <div class="mb-4 p-4 bg-amber-50 border border-amber-200 rounded-lg">
            <p class="text-sm text-amber-800 font-medium">⚠ {{ $warningMessage }}</p>
            <button wire:click="simpan"
                class="mt-2 text-sm bg-amber-600 text-white px-3 py-1.5 rounded-lg hover:bg-amber-500">
                Ya, Tetap Simpan
            </button>
        </div>
    @endif

    <form wire:submit="simpan" class="space-y-6">
        <div class="bg-white p-6 rounded-xl border border-zinc-100 shadow-sm grid sm:grid-cols-3 gap-4">
            <div>
                <x-input-label for="nomor_referensi_asal" value="Nomor Referensi" />
                <x-text-input wire:model="nomor_referensi_asal" id="nomor_referensi_asal" class="block mt-1 w-full"
                    type="text" />
                <x-input-error :messages="$errors->get('nomor_referensi_asal')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="tanggal_npb" value="Tanggal" />
                <x-text-input wire:model="tanggal_npb" id="tanggal_npb" class="block mt-1 w-full" type="date" />
                <x-input-error :messages="$errors->get('tanggal_npb')" class="mt-2" />
            </div>
            <div>
                <x-input-label value="Pihak yang Meminta" />
                <x-combobox :options="$daftarPegawai->map(
                    fn($p) => ['id' => $p->id, 'label' => $p->nama . ' (' . $p->jabatan . ')'],
                )" model="pihak_peminta_id" placeholder="Cari nama pegawai..." />
                <x-input-error :messages="$errors->get('pihak_peminta_id')" class="mt-2" />
            </div>
        </div>

        <div class="bg-white rounded-xl border border-zinc-100 shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b border-zinc-100 flex justify-between items-center">
                <h2 class="font-semibold text-zinc-900 text-sm">Daftar Barang</h2>
                <button type="button" wire:click="tambahBaris"
                    class="text-sm text-emerald-600 font-medium hover:underline">+ Tambah Baris</button>
            </div>

            <div class="divide-y divide-zinc-100">
                @foreach ($items as $index => $item)
                    <div wire:key="item-{{ $index }}" class="p-5 grid sm:grid-cols-12 gap-3 items-start">
                        <div class="sm:col-span-3">
                            <x-input-label value="Barang" />
                            <select wire:model.live="items.{{ $index }}.master_barang_id"
                                class="w-full mt-1 text-sm border-zinc-300 rounded-lg">
                                <option value="">-- Pilih --</option>
                                @foreach ($daftarMasterBarang as $mb)
                                    <option value="{{ $mb->id }}">{{ $mb->nama_barang }} ({{ $mb->kode_barang }})
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('items.' . $index . '.master_barang_id')" class="mt-1" />
                        </div>
                        <div class="sm:col-span-3">
                            <x-input-label value="Spesifikasi (opsional)" />
                            <x-text-input wire:model="items.{{ $index }}.spesifikasi"
                                class="block mt-1 w-full text-sm" type="text" />
                        </div>
                        <div class="sm:col-span-1">
                            <x-input-label value="Jml" />
                            <x-text-input wire:model="items.{{ $index }}.jumlah"
                                class="block mt-1 w-full text-sm" type="number" min="1" />
                            <x-input-error :messages="$errors->get('items.' . $index . '.jumlah')" class="mt-1" />
                        </div>
                        <div class="sm:col-span-1">
                            <x-input-label value="Satuan" />
                            <x-text-input wire:model="items.{{ $index }}.satuan"
                                class="block mt-1 w-full text-sm" type="text" />
                            <x-input-error :messages="$errors->get('items.' . $index . '.satuan')" class="mt-1" />
                        </div>
                        <div class="sm:col-span-3">
                            <x-input-label value="Keperluan" />
                            <x-text-input wire:model="items.{{ $index }}.keperluan"
                                class="block mt-1 w-full text-sm" type="text" />
                            <x-input-error :messages="$errors->get('items.' . $index . '.keperluan')" class="mt-1" />
                        </div>
                        <div class="sm:col-span-1 flex items-end h-full pb-1">
                            @if (count($items) > 1)
                                <button type="button" wire:click="hapusBaris({{ $index }})"
                                    class="text-red-500 hover:text-red-700 text-sm">✕</button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="flex items-center gap-2">
            <x-primary-button>Simpan Perubahan</x-primary-button>
            <a href="{{ route('transaksi.index') }}" wire:navigate
                class="px-4 py-2 text-sm font-medium text-zinc-600 rounded-lg hover:bg-zinc-100">Batal</a>
        </div>
    </form>
</div>
