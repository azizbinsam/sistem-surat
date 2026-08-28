<?php

use App\Models\BarangMasuk;
use App\Models\BarangMasukItem;
use App\Models\MasterBarang;
use App\Services\PersediaanService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    public BarangMasuk $barangMasuk;

    public string $nomor_bpu = '';
    public string $tanggal = '';
    public array $items = [];

    public bool $showWarning = false;
    public string $warningMessage = '';

    public function mount(BarangMasuk $barangMasuk): void
    {
        if ($barangMasuk->sekolah_id !== auth()->user()->sekolah_id) {
            abort(403);
        }

        $this->barangMasuk = $barangMasuk;
        $this->nomor_bpu = $barangMasuk->nomor_bpu;
        $this->tanggal = $barangMasuk->tanggal->format('Y-m-d');

        foreach ($barangMasuk->items as $item) {
            $this->items[] = [
                'master_barang_id' => $item->master_barang_id,
                'spesifikasi' => $item->spesifikasi,
                'satuan' => $item->satuan,
                'jumlah' => $item->jumlah,
            ];
        }
    }

    public function tambahBaris(): void
    {
        $this->items[] = ['master_barang_id' => '', 'spesifikasi' => '', 'satuan' => '', 'jumlah' => ''];
    }

    public function hapusBaris(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
        $this->showWarning = false; // reset warning, hitung ulang pas simpan lagi
    }

    public function updated($name, $value): void
    {
        if (preg_match('/^items\.(\d+)\.master_barang_id$/', $name, $m)) {
            $index = (int) $m[1];
            $barang = MasterBarang::find($value);
            if ($barang) {
                $this->items[$index]['satuan'] = $barang->satuan_default;
                if (blank($this->items[$index]['spesifikasi'])) {
                    $this->items[$index]['spesifikasi'] = $barang->spesifikasi_default ?? '';
                }
            }
        }

        if (!str($name)->startsWith('items.')) {
            return;
        }
        $this->showWarning = false; // input berubah, warning lama udah nggak valid, hitung ulang
    }

    public function simpan(PersediaanService $service): void
    {
        $validated = $this->validate([
            'nomor_bpu' => [
                'required',
                'string',
                'max:100',
                Rule::unique('barang_masuk', 'nomor_bpu')
                    ->where('sekolah_id', auth()->user()->sekolah_id)
                    ->ignore($this->barangMasuk->id),
            ],
            'tanggal' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.master_barang_id' => ['required', 'exists:master_barang,id'],
            'items.*.spesifikasi' => ['required', 'string', 'max:255'],
            'items.*.satuan' => ['required', 'string', 'max:50'],
            'items.*.jumlah' => ['required', 'integer', 'min:1'],
        ]);

        if (!$this->showWarning) {
            $originalQty = $this->barangMasuk->items->groupBy('master_barang_id')->map(fn($g) => $g->sum('jumlah'));
            $newQty = collect($validated['items'])->groupBy('master_barang_id')->map(fn($g) => collect($g)->sum('jumlah'));

            $peringatan = [];
            foreach ($originalQty as $barangId => $oldQty) {
                $newQ = $newQty->get($barangId, 0);
                $delta = $oldQty - $newQ; // positif = berkurang

                if ($delta > 0) {
                    $sisaSetelah = $service->sisaSaatIni($barangId) - $delta;
                    if ($sisaSetelah < 0) {
                        $barang = MasterBarang::find($barangId);
                        $peringatan[] = "{$barang->nama_barang} (sisa jadi {$sisaSetelah})";
                    }
                }
            }

            if (!empty($peringatan)) {
                $this->warningMessage = 'Perubahan ini bikin stok minus: ' . implode(', ', $peringatan) . '. Ada transaksi yang udah pakai stok ini.';
                $this->showWarning = true;
                return;
            }
        }

        DB::transaction(function () use ($validated) {
            $this->barangMasuk->update([
                'nomor_bpu' => $validated['nomor_bpu'],
                'tanggal' => $validated['tanggal'],
            ]);

            $this->barangMasuk->items()->delete();

            foreach ($validated['items'] as $item) {
                BarangMasukItem::create([
                    'barang_masuk_id' => $this->barangMasuk->id,
                    'master_barang_id' => $item['master_barang_id'],
                    'spesifikasi' => $item['spesifikasi'],
                    'satuan' => $item['satuan'],
                    'jumlah' => $item['jumlah'],
                ]);
            }
        });

        session()->flash('success', 'Penerimaan barang berhasil diperbarui.');
        $this->redirect(route('barang-masuk.index'), navigate: true);
    }

    public function with(): array
    {
        return [
            'daftarMasterBarang' => MasterBarang::where('sekolah_id', auth()->user()->sekolah_id)
                ->orderBy('nama_barang')
                ->get(),
        ];
    }
}; ?>

<div class="max-w-3xl">
    <div class="mb-6">
        <a href="{{ route('barang-masuk.index') }}" wire:navigate
            class="text-sm text-zinc-500 hover:text-zinc-700 inline-flex items-center gap-1 mb-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Kembali
        </a>
        <h1 class="text-2xl font-bold text-zinc-900">Edit Penerimaan Barang</h1>
    </div>

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
        <div class="bg-white p-6 rounded-xl border border-zinc-100 shadow-sm grid sm:grid-cols-2 gap-4">
            <div>
                <x-input-label for="nomor_bpu" value="Nomor BPU" />
                <x-text-input wire:model="nomor_bpu" id="nomor_bpu" class="block mt-1 w-full" type="text" />
                <x-input-error :messages="$errors->get('nomor_bpu')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="tanggal" value="Tanggal" />
                <x-text-input wire:model="tanggal" id="tanggal" class="block mt-1 w-full" type="date" />
                <x-input-error :messages="$errors->get('tanggal')" class="mt-2" />
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
                        <div class="sm:col-span-4">
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
                            <x-input-label value="Spesifikasi" />
                            <x-text-input wire:model="items.{{ $index }}.spesifikasi"
                                class="block mt-1 w-full text-sm" type="text" />
                            <x-input-error :messages="$errors->get('items.' . $index . '.spesifikasi')" class="mt-1" />
                        </div>
                        <div class="sm:col-span-2">
                            <x-input-label value="Satuan" />
                            <x-text-input wire:model="items.{{ $index }}.satuan"
                                class="block mt-1 w-full text-sm" type="text" />
                            <x-input-error :messages="$errors->get('items.' . $index . '.satuan')" class="mt-1" />
                        </div>
                        <div class="sm:col-span-2">
                            <x-input-label value="Jumlah" />
                            <x-text-input wire:model="items.{{ $index }}.jumlah"
                                class="block mt-1 w-full text-sm" type="number" min="1" />
                            <x-input-error :messages="$errors->get('items.' . $index . '.jumlah')" class="mt-1" />
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

        <div class="flex items-center gap-3">
            <x-primary-button>Simpan Perubahan</x-primary-button>
            <a href="{{ route('barang-masuk.index') }}" wire:navigate
                class="text-sm text-zinc-500 hover:text-zinc-700">Batal</a>
        </div>
    </form>
</div>
