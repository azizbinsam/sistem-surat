<?php

use App\Imports\TransaksiKeluarReader;
use App\Models\MasterBarang;
use App\Models\Pegawai;
use App\Models\Transaksi;
use App\Models\TransaksiItem;
use App\Services\BarangMatcher;
use App\Services\NomorSuratService;
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
    public array $notifGagalMappingPeminta = [];
    public array $notifNamaBerbeda = [];

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

            // "Spesifikasi" sekarang opsional (fallback ke Nama Barang master pas generate surat)
            $wajib = ['tanggal', 'nomor_referensi', 'nama_barang', 'jumlah', 'satuan', 'keperluan'];
            $kosong = collect($wajib)->filter(fn($k) => blank($row[$k] ?? null));

            if ($kosong->isNotEmpty()) {
                $this->errorMsg = 'Baris ' . ($i + 2) . ': kolom ' . $kosong->implode(', ') . ' tidak boleh kosong.';
                return;
            }

            $masterBarang = $matcher->cari($row['nama_barang'], $sekolahId);

            $namaPeminta = trim((string) ($row['nama_peminta'] ?? ''));
            $jabatanPeminta = trim((string) ($row['jabatan_peminta'] ?? ''));
            $pihakPemintaId = null;

            if ($namaPeminta && $jabatanPeminta) {
                $pihakPemintaId = Pegawai::where('sekolah_id', $sekolahId)->where('nama', $namaPeminta)->where('jabatan', $jabatanPeminta)->value('id');
            }

            $parsed[] = [
                'tanggal' => \Carbon\Carbon::parse($row['tanggal'])->format('Y-m-d'),
                'nomor_referensi' => trim((string) $row['nomor_referensi']),
                'nama_barang' => trim($row['nama_barang']),
                'spesifikasi' => trim((string) ($row['spesifikasi'] ?? '')),
                'jumlah' => (int) $row['jumlah'],
                'satuan' => trim($row['satuan']),
                'keperluan' => trim($row['keperluan']),
                'master_barang_id' => $masterBarang?->id,
                'was_auto_matched' => $masterBarang !== null,
                'kode_baru' => '',
                'satuan_baru' => '',
                'nama_peminta' => $namaPeminta,
                'jabatan_peminta' => $jabatanPeminta,
                'pihak_peminta_id' => $pihakPemintaId,
                'nomor_npb_override' => trim((string) ($row['nomor_npb'] ?? '')),
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

        // Validasi §8.4: dalam 1 Nomor Referensi, Nama Peminta harus konsisten antar baris
        $gagalMapping = [];
        $namaBerbeda = [];
        foreach (collect($parsed)->groupBy('nomor_referensi') as $referensi => $items) {
            $namaUnik = $items->pluck('nama_peminta')->filter()->unique();
            if ($namaUnik->count() > 1) {
                $namaBerbeda[] = $referensi . ' (' . $namaUnik->implode(' vs ') . ')';
            }

            $adaNamaDiisi = $items->pluck('nama_peminta')->filter()->isNotEmpty();
            $adaYangKetemu = $items->pluck('pihak_peminta_id')->filter()->isNotEmpty();
            if ($adaNamaDiisi && !$adaYangKetemu) {
                $gagalMapping[] = $referensi;
            }
        }
        $this->notifGagalMappingPeminta = $gagalMapping;
        $this->notifNamaBerbeda = $namaBerbeda;

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

    public function simpan(NomorSuratService $nomorService): void
    {
        $belumMapping = collect($this->rows)->filter(fn($r) => blank($r['master_barang_id']));

        if ($belumMapping->isNotEmpty()) {
            $this->errorMsg = 'Masih ada barang yang belum di-mapping ke Master Barang. Lengkapi dulu semua baris.';
            return;
        }

        $sekolahId = auth()->user()->sekolah_id;
        $matcher = app(BarangMatcher::class);

        DB::transaction(function () use ($sekolahId, $matcher, $nomorService) {
            $grup = collect($this->rows)->groupBy('nomor_referensi');

            foreach ($grup as $nomorReferensi => $items) {
                $pihakPemintaId = $items->pluck('pihak_peminta_id')->filter()->first();
                $nomorOverride = $items->pluck('nomor_npb_override')->filter()->first();
                $tanggalNpb = $items->first()['tanggal'];

                $dataTransaksi = [
                    'sekolah_id' => $sekolahId,
                    'nomor_referensi_asal' => $nomorReferensi,
                    'tanggal_npb' => $tanggalNpb,
                    'pihak_peminta_id' => $pihakPemintaId,
                    'status' => 'draft',
                ];

                if ($nomorOverride) {
                    // Data historis: kalau cuma diisi angka urutnya aja (mis. "0012" atau "12"),
                    // sistem susun nomor lengkap pakai format standar sekarang (kode klasifikasi +
                    // kode sekolah + bulan dari tanggal baris itu) — biar user nggak perlu ngetik
                    // nomor lengkap yang panjang & rawan salah ketik. Kalau isinya udah nomor
                    // lengkap (ada tanda "/"), dipakai APA ADANYA (buat format lama yang beda).
                    $nomorNpbFinal = ctype_digit($nomorOverride) ? $nomorService->formatNpb(auth()->user()->sekolah, \Carbon\Carbon::parse($tanggalNpb), (int) $nomorOverride) : $nomorOverride;

                    // Nomor historis SENGAJA tidak mempengaruhi/menaikkan counter
                    // nomor_urut_terakhir (PRD §8.1) — beda dari generateNomorNpb().
                    $dataTransaksi['nomor_npb'] = $nomorNpbFinal;
                    $dataTransaksi['nomor_spb'] = $nomorService->turunanSpb($nomorNpbFinal);
                    $dataTransaksi['nomor_sppb'] = $nomorService->turunanSppb($nomorNpbFinal);
                    $dataTransaksi['tanggal_spb'] = $tanggalNpb;
                    $dataTransaksi['tanggal_sppb'] = $tanggalNpb;
                    $dataTransaksi['status'] = 'selesai';
                }

                $transaksi = Transaksi::create($dataTransaksi);

                foreach ($items as $item) {
                    TransaksiItem::create([
                        'transaksi_id' => $transaksi->id,
                        'master_barang_id' => $item['master_barang_id'],
                        'spesifikasi' => $item['spesifikasi'] ?: null,
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

        $pesan = 'Data transaksi berhasil di-transpose jadi draft.';
        if (!empty($this->notifGagalMappingPeminta)) {
            $pesan .= ' Nomor Referensi yang gagal auto-mapping pihak peminta: ' . implode(', ', $this->notifGagalMappingPeminta) . ' — lengkapi manual di halaman berikutnya.';
        } else {
            $pesan .= ' Lanjutkan mapping pihak peminta di halaman berikutnya kalau masih ada yang kosong.';
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

        <h1 class="text-2xl font-bold text-zinc-900">
            Upload Transaksi Keluar
        </h1>

        <p class="text-sm text-zinc-500 mt-1">
            Upload data transaksi keluar sekaligus lewat file Excel.
        </p>
    </div>


    {{-- Indikator Step --}}
    <div class="flex items-center gap-3 mb-6">

        {{-- Step 1 --}}
        <div class="flex items-center gap-2">
            <span
                class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-semibold
                {{ $step === 'upload' ? 'bg-zinc-800 text-white' : 'bg-emerald-100 text-emerald-700' }}">

                @if ($step === 'review')
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                @else
                    1
                @endif
            </span>

            <span
                class="text-sm font-medium
                {{ $step === 'upload' ? 'text-zinc-900' : 'text-zinc-500' }}">
                Upload File
            </span>
        </div>

        <div class="flex-1 h-px bg-zinc-200"></div>

        {{-- Step 2 --}}
        <div class="flex items-center gap-2">
            <span
                class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-semibold
                {{ $step === 'review' ? 'bg-zinc-800 text-white' : 'bg-zinc-100 text-zinc-400' }}">
                2
            </span>

            <span
                class="text-sm font-medium
                {{ $step === 'review' ? 'text-zinc-900' : 'text-zinc-400' }}">
                Cek & Simpan
            </span>
        </div>

    </div>


    {{-- Error --}}
    @if ($errorMsg)
        <div class="mb-4 p-3 bg-red-50 text-red-700 rounded-lg text-sm border border-red-100">
            {{ $errorMsg }}
        </div>
    @endif


    {{-- STEP 1 --}}
    @if ($step === 'upload')
        {{-- Informasi Format --}}
        <div class="mb-4 flex items-start gap-3 p-4 bg-zinc-50 rounded-lg border border-zinc-100">

            <svg class="w-5 h-5 mt-0.5 flex-shrink-0 text-zinc-500" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13 16h-1v-4h-1m1-4h.01M12 21a9 9 0 100-18 9 9 0 000 18z" />
            </svg>

            <div class="text-sm text-zinc-600">

                <p>
                    Kolom wajib:
                    <strong class="text-zinc-800">
                        Tanggal, Nomor Referensi, Nama Barang, Jumlah, Satuan, dan Keperluan
                    </strong>.
                </p>

                <p class="mt-1.5">
                    Kolom opsional:
                    <strong class="text-zinc-800">Spesifikasi</strong>,
                    <strong class="text-zinc-800">Nama Peminta + Jabatan Peminta</strong>,
                    dan <strong class="text-zinc-800">Nomor NPB</strong>.
                </p>

                <p class="mt-1.5">
                    Satu Nomor Referensi dapat muncul pada beberapa baris untuk item berbeda
                    dan akan otomatis digabung menjadi satu draft transaksi.
                </p>

                <p class="mt-1.5 text-xs text-zinc-500">
                    Untuk data historis, Nomor NPB dapat diisi dengan angka urut saja
                    (contoh: <code class="px-1 py-0.5 bg-white border border-zinc-200 rounded">0012</code>)
                    dan sistem akan melengkapinya ke format standar.
                </p>

                <a href="{{ route('transaksi.template') }}"
                    class="inline-flex items-center gap-1.5 mt-2 text-emerald-700
                        font-medium hover:text-emerald-800 hover:underline">

                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 3v12m0 0l-4-4m4 4l4-4M5 21h14" />
                    </svg>

                    Download Template Excel
                </a>

            </div>
        </div>


        {{-- Upload Form --}}
        <form wire:submit="parse" class="space-y-4 bg-white p-6 rounded-xl border border-zinc-100 shadow-sm">

            <div>
                <x-input-label for="file" value="File Excel" />

                <input type="file" wire:model="file" id="file"
                    class="block mt-1 w-full text-sm text-zinc-600
                        file:mr-4 file:py-2 file:px-4
                        file:rounded-lg file:border-0
                        file:text-sm file:font-medium
                        file:bg-zinc-100 file:text-zinc-700
                        hover:file:bg-zinc-200">

                <x-input-error :messages="$errors->get('file')" class="mt-2" />

                <div wire:loading wire:target="file" class="text-xs text-zinc-500 mt-1">
                    Mengunggah file...
                </div>
            </div>

            <div class="flex items-center gap-2 pt-2">
                <x-primary-button>
                    Proses & Cek Data
                </x-primary-button>

                <a href="{{ route('transaksi.index') }}" wire:navigate
                    class="px-4 py-2 text-sm font-medium text-zinc-600 rounded-lg hover:bg-zinc-100">
                    Batal
                </a>
            </div>

        </form>
    @endif


    {{-- STEP 2 --}}
    @if ($step === 'review')

        {{-- Informasi Review --}}
        <div class="mb-3 flex items-start gap-3 p-3 bg-zinc-50 rounded-lg border border-zinc-100">

            <svg class="w-5 h-5 mt-0.5 flex-shrink-0 text-zinc-500" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13 16h-1v-4h-1m1-4h.01M12 21a9 9 0 100-18 9 9 0 000 18z" />
            </svg>

            <p class="text-sm text-zinc-600">
                Ditemukan
                <strong class="text-zinc-800">{{ count($rows) }}</strong>
                baris item.
                Barang yang sudah dikenali otomatis ditandai
                <span class="text-emerald-700 font-medium">hijau</span>,
                sedangkan barang yang memerlukan mapping manual ditandai
                <span class="text-amber-700 font-medium">kuning</span>.
            </p>

        </div>


        {{-- Notifikasi Mapping Peminta --}}
        @if (!empty($notifGagalMappingPeminta))
            <div class="mb-3 p-3 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-800">
                Gagal auto-mapping pihak peminta karena nama/jabatan tidak cocok
                dengan data Pegawai pada Nomor Referensi:

                <strong>
                    {{ implode(', ', $notifGagalMappingPeminta) }}
                </strong>.

                Nanti dapat dilengkapi manual di halaman daftar transaksi.
            </div>
        @endif


        {{-- Notifikasi Nama Berbeda --}}
        @if (!empty($notifNamaBerbeda))
            <div
                class="mb-3 flex items-start gap-2 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-800">

                <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>

                <div>
                    Nama Peminta berbeda-beda dalam satu Nomor Referensi
                    (indikasi salah ketik di Excel):

                    <strong>
                        {{ implode('; ', $notifNamaBerbeda) }}
                    </strong>.
                </div>

            </div>
        @endif


        {{-- Tabel --}}
        <div class="bg-white rounded-xl border border-zinc-100 shadow-sm overflow-x-auto mb-4">

            <table class="min-w-full divide-y divide-zinc-100 text-sm">
                <thead class="bg-zinc-50">
                    <tr>
                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-zinc-500 uppercase tracking-wider">No. Referensi</th>
                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-zinc-500 uppercase tracking-wider">Tanggal</th>
                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-zinc-500 uppercase tracking-wider">Nama Barang (Excel)
                        </th>
                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-zinc-500 uppercase tracking-wider">Jumlah</th>
                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-zinc-500 uppercase tracking-wider">Mapping ke Master
                            Barang</th>
                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-zinc-500 uppercase tracking-wider">Peminta</th>
                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-zinc-500 uppercase tracking-wider">Nomor NPB</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @foreach ($rows as $index => $row)
                        <tr wire:key="row-{{ $index }}"
                            class="{{ $row['master_barang_id'] ? 'bg-green-50' : 'bg-amber-50' }}">
                            <td class="px-3 py-2">{{ $row['nomor_referensi'] }}</td>
                            <td class="px-3 py-2">{{ $row['tanggal'] }}</td>
                            <td class="px-3 py-2">{{ $row['nama_barang'] }} <span
                                    class="text-gray-400">({{ $row['spesifikasi'] ?: 'pakai default' }})</span></td>
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
                            <td class="px-3 py-2 text-xs">
                                @if ($row['nama_peminta'])
                                    @if ($row['pihak_peminta_id'])
                                        <span class="text-green-700">✓ {{ $row['nama_peminta'] }}</span>
                                    @else
                                        <span class="text-amber-700">⚠ {{ $row['nama_peminta'] }} (gagal cocok)</span>
                                    @endif
                                @else
                                    <span class="text-zinc-400">-</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-xs">
                                {{ $row['nomor_npb_override'] ?: '-' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

        </div>

        <div class="flex items-center gap-2">

            <x-primary-button wire:click="simpan">
                Simpan Semua
            </x-primary-button>

            <button type="button" wire:click="batalkanReview"
                class="px-4 py-2 text-sm font-medium text-zinc-600 rounded-lg hover:bg-zinc-100">
                Batal, Upload Ulang
            </button>

        </div>

    @endif

</div>