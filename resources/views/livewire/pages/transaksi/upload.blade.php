<?php

use App\Imports\TransaksiKeluarReader;
use App\Models\MasterBarang;
use App\Models\Transaksi;
use App\Models\TransaksiItem;
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
    public array $showCreateForm = [];

    public function parse(): void
    {
        $this->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120'],
        ]);

        $this->errorMsg = null;
        $sekolahId = auth()->user()->sekolah_id;
        $matcher = app(BarangMatcher::class);

        $reader = new TransaksiKeluarReader();
        Excel::import($reader, $this->file);

        $parsed = [];
        foreach ($reader->rows as $i => $row) {
            if (blank($row['nomor_referensi'] ?? null) && blank($row['nama_barang'] ?? null)) {
                continue;
            }

            $wajib = ['tanggal', 'nomor_referensi', 'nama_barang', 'spesifikasi', 'jumlah', 'satuan', 'keperluan'];
            $kosong = collect($wajib)->filter(fn($k) => blank($row[$k] ?? null));

            if ($kosong->isNotEmpty()) {
                $this->errorMsg = 'Baris ' . ($i + 2) . ': kolom ' . $kosong->implode(', ') . ' tidak boleh kosong.';
                return;
            }

            $masterBarang = $matcher->cari($row['nama_barang'], $sekolahId);

            $parsed[] = [
                'tanggal' => \Carbon\Carbon::parse($row['tanggal'])->format('Y-m-d'),
                'nomor_referensi' => trim((string) $row['nomor_referensi']),
                'nama_barang' => trim($row['nama_barang']),
                'spesifikasi' => trim($row['spesifikasi']),
                'jumlah' => (int) $row['jumlah'],
                'satuan' => trim($row['satuan']),
                'keperluan' => trim($row['keperluan']),
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

        $referensiDiUpload = collect($parsed)->pluck('nomor_referensi')->unique();
        $sudahAda = Transaksi::where('sekolah_id', $sekolahId)->whereIn('nomor_referensi_asal', $referensiDiUpload)->pluck('nomor_referensi_asal');

        if ($sudahAda->isNotEmpty()) {
            $this->errorMsg = 'Nomor Referensi berikut sudah pernah diupload sebelumnya: ' . $sudahAda->implode(', ');
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
            $grup = collect($this->rows)->groupBy('nomor_referensi');

            foreach ($grup as $nomorReferensi => $items) {
                $transaksi = Transaksi::create([
                    'sekolah_id' => $sekolahId,
                    'nomor_referensi_asal' => $nomorReferensi,
                    'tanggal_npb' => $items->first()['tanggal'],
                    'status' => 'draft',
                ]);

                foreach ($items as $item) {
                    TransaksiItem::create([
                        'transaksi_id' => $transaksi->id,
                        'master_barang_id' => $item['master_barang_id'],
                        'spesifikasi' => $item['spesifikasi'],
                        'jumlah' => $item['jumlah'],
                        'satuan' => $item['satuan'],
                        'keperluan' => $item['keperluan'],
                    ]);

                    if (!$item['was_auto_matched']) {
                        $matcher->simpanAlias($item['nama_barang'], $item['spesifikasi'], $item['master_barang_id'], $sekolahId);
                    }
                }
            }
        });

        session()->flash('success', 'Data transaksi berhasil di-transpose jadi draft. Lanjutkan mapping pihak peminta di halaman berikutnya.');
        $this->redirect(route('transaksi.index'), navigate: true);
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
    <h2 class="text-lg font-semibold text-zinc-900 mb-4">Upload Transaksi Keluar</h2>

    @if ($errorMsg)
        <div class="mb-4 p-3 bg-red-50 text-red-700 rounded-md text-sm">{{ $errorMsg }}</div>
    @endif

    @if ($step === 'upload')
        <div class="mb-4 p-4 bg-zinc-50 rounded-md text-sm text-zinc-700">
            Format kolom wajib: <strong>Tanggal, Nomor Referensi, Nama Barang, Spesifikasi, Jumlah, Satuan,
                Keperluan</strong>.
            Baris dengan Nomor Referensi sama otomatis digabung jadi 1 draft transaksi (1 bundel surat NPB+SPB+SPPB).
            <a href="{{ route('transaksi.template') }}" class="block mt-2 text-zinc-900 underline">Download Template
                Excel</a>
        </div>

        <form wire:submit="parse" class="space-y-4 bg-white p-6 rounded-md shadow">
            <div>
                <x-input-label for="file" value="File Excel" />
                <input type="file" wire:model="file" id="file" class="block mt-1 w-full text-sm">
                <x-input-error :messages="$errors->get('file')" class="mt-2" />
                <div wire:loading wire:target="file" class="text-xs text-zinc-500 mt-1">Mengunggah file...</div>
            </div>

            <div class="flex items-center gap-3">
                <x-primary-button>Proses & Cek Data</x-primary-button>
                <a href="{{ route('transaksi.index') }}" wire:navigate
                    class="text-sm text-zinc-600 hover:underline">Batal</a>
            </div>
        </form>
    @endif

    @if ($step === 'review')
        <div class="mb-4 p-3 bg-zinc-50 rounded-md text-sm text-zinc-700">
            Ditemukan <strong>{{ count($rows) }}</strong> baris item. Barang bertanda <span
                class="text-green-700 font-medium">hijau</span> sudah otomatis dikenali.
            Barang bertanda <span class="text-amber-700 font-medium">kuning</span> perlu di-mapping manual dulu sebelum
            disimpan.
        </div>

        <div class="bg-white rounded-md shadow overflow-x-auto mb-4">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-zinc-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-zinc-600 uppercase">No. Referensi</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-zinc-600 uppercase">Tanggal</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-zinc-600 uppercase">Nama Barang (Excel)
                        </th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-zinc-600 uppercase">Jumlah</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-zinc-600 uppercase">Mapping ke Master
                            Barang</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach ($rows as $index => $row)
                        <tr wire:key="row-{{ $index }}"
                            class="{{ $row['master_barang_id'] ? 'bg-green-50' : 'bg-amber-50' }}">
                            <td class="px-3 py-2">{{ $row['nomor_referensi'] }}</td>
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
                                                ({{ $mb->kode_barang }})</option>
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
            <button type="button" wire:click="batalkanReview" class="text-sm text-zinc-600 hover:underline">Batal,
                Upload Ulang</button>
        </div>
    @endif
</div>
