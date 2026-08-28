<?php

use App\Imports\PenerimaanBarangReader;
use App\Models\BarangMasuk;
use App\Models\BarangMasukItem;
use App\Models\MasterBarang;
use App\Services\BarangMatcher;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads;

    public string $step = 'upload'; // upload | review

    public $file;
    public array $rows = [];
    public ?string $errorMsg = null;

    // toggle form "buat barang baru" per index baris
    public array $showCreateForm = [];

    public function parse(): void
    {
        $this->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120'],
        ]);

        $this->errorMsg = null;
        $sekolahId = auth()->user()->sekolah_id;
        $matcher = app(BarangMatcher::class);

        $reader = new PenerimaanBarangReader();
        Excel::import($reader, $this->file);

        $parsed = [];
        foreach ($reader->rows as $i => $row) {
            // lewati baris kosong
            if (blank($row['nomor_bpu'] ?? null) && blank($row['nama_barang'] ?? null)) {
                continue;
            }

            $wajib = ['tanggal', 'nomor_bpu', 'nama_barang', 'spesifikasi', 'satuan', 'jumlah'];
            $kosong = collect($wajib)->filter(fn($k) => blank($row[$k] ?? null));

            if ($kosong->isNotEmpty()) {
                $this->errorMsg = 'Baris ' . ($i + 2) . ': kolom ' . $kosong->implode(', ') . ' tidak boleh kosong.';
                return;
            }

            $masterBarang = $matcher->cari($row['nama_barang'], $sekolahId);

            $parsed[] = [
                'tanggal' => \Carbon\Carbon::parse($row['tanggal'])->format('Y-m-d'),
                'nomor_bpu' => trim($row['nomor_bpu']),
                'nama_barang' => trim($row['nama_barang']),
                'spesifikasi' => trim($row['spesifikasi']),
                'satuan' => trim($row['satuan']),
                'jumlah' => (int) $row['jumlah'],
                'master_barang_id' => $masterBarang?->id,
                'was_auto_matched' => $masterBarang !== null,
                'kode_baru' => '',
                'satuan_baru' => '',
            ];
        }

        if (empty($parsed)) {
            $this->errorMsg = 'File tidak berisi data yang bisa diproses.';
            return;
        }

        // cek nomor BPU yang sudah pernah dipakai sebelumnya (biar nggak duplikat)
        $nomorBpuDiUpload = collect($parsed)->pluck('nomor_bpu')->unique();
        $sudahAda = BarangMasuk::where('sekolah_id', $sekolahId)->whereIn('nomor_bpu', $nomorBpuDiUpload)->pluck('nomor_bpu');

        if ($sudahAda->isNotEmpty()) {
            $this->errorMsg = 'Nomor BPU berikut sudah pernah diupload sebelumnya: ' . $sudahAda->implode(', ');
            return;
        }

        $this->rows = $parsed;
        $this->step = 'review';
    }

    public function buatBarangBaru(int $index): void
    {
        $this->validate(
            [
                "rows.$index.kode_baru" => ['required', 'string', 'max:100'],
                "rows.$index.satuan_baru" => ['required', 'string', 'max:50'],
            ],
            [],
            [
                "rows.$index.kode_baru" => 'Kode Barang',
                "rows.$index.satuan_baru" => 'Satuan',
            ],
        );

        $barang = MasterBarang::create([
            'sekolah_id' => auth()->user()->sekolah_id,
            'kode_barang' => $this->rows[$index]['kode_baru'],
            'nama_barang' => $this->rows[$index]['nama_barang'],
            'satuan_default' => $this->rows[$index]['satuan_baru'],
        ]);

        $this->rows[$index]['master_barang_id'] = $barang->id;
        $this->showCreateForm[$index] = false;
    }

    public function batalkanReview(): void
    {
        $this->reset(['step', 'rows', 'file', 'errorMsg', 'showCreateForm']);
    }

    public function simpan(): void
    {
        $belumMapping = collect($this->rows)->filter(fn($r) => blank($r['master_barang_id']));

        if ($belumMapping->isNotEmpty()) {
            $this->errorMsg = 'Masih ada barang yang belum di-mapping ke Master Barang. Lengkapi dulu semua baris.';
            return;
        }

        $sekolahId = auth()->user()->sekolah_id;
        $matcher = app(BarangMatcher::class);

        DB::transaction(function () use ($sekolahId, $matcher) {
            $grup = collect($this->rows)->groupBy('nomor_bpu');

            foreach ($grup as $nomorBpu => $items) {
                $barangMasuk = BarangMasuk::create([
                    'sekolah_id' => $sekolahId,
                    'nomor_bpu' => $nomorBpu,
                    'tanggal' => $items->first()['tanggal'],
                ]);

                foreach ($items as $item) {
                    BarangMasukItem::create([
                        'barang_masuk_id' => $barangMasuk->id,
                        'master_barang_id' => $item['master_barang_id'],
                        'spesifikasi' => $item['spesifikasi'],
                        'satuan' => $item['satuan'],
                        'jumlah' => $item['jumlah'],
                    ]);

                    // kalau ini hasil mapping manual (bukan exact match otomatis), simpan sebagai alias
                    if (!$item['was_auto_matched']) {
                        $matcher->simpanAlias($item['nama_barang'], $item['spesifikasi'], $item['master_barang_id'], $sekolahId);
                    }
                }
            }
        });

        session()->flash('success', 'Data penerimaan barang berhasil disimpan.');
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

<div class="max-w-4xl">
    <div class="mb-6">
        <a href="{{ route('barang-masuk.index') }}" wire:navigate
            class="text-sm text-zinc-500 hover:text-zinc-700 inline-flex items-center gap-1 mb-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Kembali
        </a>
        <h1 class="text-2xl font-bold text-zinc-900">Upload Penerimaan Barang (BPU)</h1>
        <p class="text-sm text-zinc-500 mt-1">Upload data BPU sekaligus lewat file Excel.</p>
    </div>

    {{-- Indikator step --}}
    <div class="flex items-center gap-3 mb-6">
        <div class="flex items-center gap-2">
            <span
                class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-semibold {{ $step === 'upload' ? 'bg-zinc-800 text-white' : 'bg-emerald-100 text-emerald-700' }}">
                @if ($step === 'review')
                    ✓
                @else
                    1
                @endif
            </span>
            <span class="text-sm font-medium {{ $step === 'upload' ? 'text-zinc-900' : 'text-zinc-500' }}">Upload
                File</span>
        </div>
        <div class="flex-1 h-px bg-zinc-200"></div>
        <div class="flex items-center gap-2">
            <span
                class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-semibold {{ $step === 'review' ? 'bg-zinc-800 text-white' : 'bg-zinc-100 text-zinc-400' }}">
                2
            </span>
            <span class="text-sm font-medium {{ $step === 'review' ? 'text-zinc-900' : 'text-zinc-400' }}">Cek &
                Simpan</span>
        </div>
    </div>

    @if ($errorMsg)
        <div class="mb-4 p-3 bg-red-50 text-red-700 rounded-lg text-sm border border-red-100">{{ $errorMsg }}</div>
    @endif

    @if ($step === 'upload')
        <div class="mb-4 p-4 bg-zinc-50 rounded-lg text-sm text-zinc-700 border border-zinc-100">
            Format kolom wajib: <strong>Tanggal, Nomor BPU, Nama Barang, Spesifikasi, Satuan, Jumlah</strong>.
            1 Nomor BPU boleh muncul di beberapa baris (untuk item berbeda) — otomatis digabung jadi 1 transaksi.
            <a href="{{ route('barang-masuk.template') }}"
                class="block mt-2 text-emerald-700 font-medium hover:underline">⬇ Download Template Excel</a>
        </div>

        <form wire:submit="parse" class="space-y-4 bg-white p-6 rounded-xl border border-zinc-100 shadow-sm">
            <div>
                <x-input-label for="file" value="File Excel" />
                <input type="file" wire:model="file" id="file"
                    class="block mt-1 w-full text-sm text-zinc-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-zinc-100 file:text-zinc-700 hover:file:bg-zinc-200">
                <x-input-error :messages="$errors->get('file')" class="mt-2" />
                <div wire:loading wire:target="file" class="text-xs text-zinc-500 mt-1">Mengunggah file...</div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <x-primary-button>Proses & Cek Data</x-primary-button>
                <a href="{{ route('barang-masuk.index') }}" wire:navigate
                    class="text-sm text-zinc-500 hover:text-zinc-700">Batal</a>
            </div>
        </form>
    @endif

    @if ($step === 'review')
        <div class="mb-4 p-3 bg-zinc-50 rounded-lg text-sm text-zinc-700 border border-zinc-100">
            Ditemukan <strong>{{ count($rows) }}</strong> baris item. Barang bertanda <span
                class="text-green-700 font-medium">hijau</span> sudah otomatis dikenali.
            Barang bertanda <span class="text-amber-700 font-medium">kuning</span> perlu di-mapping manual dulu sebelum
            disimpan.
        </div>

        <div class="bg-white rounded-xl border border-zinc-100 shadow-sm overflow-hidden mb-4">
            <table class="min-w-full divide-y divide-zinc-100 text-sm">
                <thead class="bg-zinc-50">
                    <tr>
                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-zinc-500 uppercase tracking-wider">
                            Nomor BPU</th>
                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-zinc-500 uppercase tracking-wider">
                            Tanggal</th>
                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-zinc-500 uppercase tracking-wider">
                            Nama Barang (Excel)
                        </th>
                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-zinc-500 uppercase tracking-wider">
                            Jumlah</th>
                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-zinc-500 uppercase tracking-wider">
                            Mapping ke Master
                            Barang</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @foreach ($rows as $index => $row)
                        <tr wire:key="row-{{ $index }}"
                            class="{{ $row['master_barang_id'] ? 'bg-green-50' : 'bg-amber-50' }}">
                            <td class="px-3 py-2">{{ $row['nomor_bpu'] }}</td>
                            <td class="px-3 py-2">{{ $row['tanggal'] }}</td>
                            <td class="px-3 py-2">{{ $row['nama_barang'] }} <span
                                    class="text-gray-400">({{ $row['spesifikasi'] }})</span></td>
                            <td class="px-3 py-2">{{ $row['jumlah'] }} {{ $row['satuan'] }}</td>
                            <td class="px-3 py-2">
                                @if ($row['was_auto_matched'])
                                    <span class="text-green-700">✓
                                        {{ $daftarMasterBarang->find($row['master_barang_id'])?->nama_barang }}</span>
                                @else
                                    <select wire:model="rows.{{ $index }}.master_barang_id"
                                        class="w-full text-sm border-gray-300 rounded-md">
                                        <option value="">-- Belum dipilih --</option>
                                        @foreach ($daftarMasterBarang as $mb)
                                            <option value="{{ $mb->id }}">{{ $mb->nama_barang }}
                                                ({{ $mb->kode_barang }})
                                            </option>
                                        @endforeach
                                    </select>

                                    @if (!$row['master_barang_id'])
                                        <button type="button"
                                            wire:click="$set('showCreateForm.{{ $index }}', true)"
                                            class="text-xs text-zinc-600 hover:underline mt-1">
                                            atau buat sebagai barang baru
                                        </button>
                                    @endif

                                    @if ($showCreateForm[$index] ?? false)
                                        <div class="mt-2 p-2 border border-zinc-200 rounded space-y-1">
                                            <input type="text" wire:model="rows.{{ $index }}.kode_baru"
                                                placeholder="Kode Barang"
                                                class="w-full text-xs border-gray-300 rounded">
                                            <input type="text" wire:model="rows.{{ $index }}.satuan_baru"
                                                placeholder="Satuan" class="w-full text-xs border-gray-300 rounded">
                                            <button type="button" wire:click="buatBarangBaru({{ $index }})"
                                                class="text-xs bg-zinc-800 text-white px-2 py-1 rounded">Buat &
                                                Pakai</button>
                                        </div>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex items-center gap-3">
            <x-primary-button wire:click="simpan">Simpan Semua</x-primary-button>
            <button type="button" wire:click="batalkanReview" class="text-sm text-zinc-500 hover:text-zinc-700">Batal,
                Upload Ulang</button>
        </div>
    @endif
</div>
