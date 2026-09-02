<?php

use App\Models\Pegawai;
use App\Models\Transaksi;
use App\Services\NomorSuratService;
use App\Services\PersediaanService;
use App\Services\SuratPdfGenerator;
use App\Services\SuratWordGenerator;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Livewire\Concerns\HasCustomPagination;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination, HasCustomPagination;

    public string $search = '';
    public string $filterStatus = '';
    public string $sortBy = 'tanggal_npb';
    public string $sortDir = 'desc';
    public string $perPage = '10';

    protected array $kolomBolehSort = ['nomor_referensi_asal', 'nomor_npb', 'tanggal_npb'];

    public array $pihakPeminta = [];
    public array $selected = [];
    public ?string $errorMsg = null;

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
        $this->selected = [];
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
        $this->selected = [];
    }

    public function sortir(string $kolom): void
    {
        if (!in_array($kolom, $this->kolomBolehSort, true)) {
            return;
        }

        if ($this->sortBy === $kolom) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $kolom;
            $this->sortDir = 'asc';
        }
    }

    public function updated($name, $value): void
    {
        if (Str::startsWith($name, 'pihakPeminta.')) {
            $transaksiId = (int) Str::after($name, 'pihakPeminta.');

            $transaksi = Transaksi::where('sekolah_id', auth()->user()->sekolah_id)->findOrFail($transaksiId);
            $transaksi->update(['pihak_peminta_id' => $value ?: null]);

            session()->flash('success', 'Pihak peminta berhasil diperbarui.');
        }
    }

    // ===== Hapus (satuan & bulk), lewat modal konfirmasi =====
    public ?int $idHapusSatuan = null;
    public bool $modeHapusBulk = false;
    public bool $modalHapusTampil = false;
    /** Ringkasan transaksi yang bakal kena hapus, buat ditampilin di modal (termasuk yang nomor suratnya bakal hilang). */
    public array $transaksiTerpengaruh = [];

    public function mintaHapusSatuan(int $id): void
    {
        $this->idHapusSatuan = $id;
        $this->modeHapusBulk = false;
        $this->transaksiTerpengaruh = $this->ambilRingkasanTransaksi([$id]);
        $this->modalHapusTampil = true;
    }

    public function mintaHapusBulk(): void
    {
        if (empty($this->selected)) {
            return;
        }

        $this->modeHapusBulk = true;
        $this->transaksiTerpengaruh = $this->ambilRingkasanTransaksi($this->selected);
        $this->modalHapusTampil = true;
    }

    protected function ambilRingkasanTransaksi(array $ids): array
    {
        return Transaksi::where('sekolah_id', auth()->user()->sekolah_id)
            ->whereIn('id', $ids)
            ->get(['id', 'nomor_referensi_asal', 'nomor_npb'])
            ->map(fn($t) => ['nomor_referensi_asal' => $t->nomor_referensi_asal, 'nomor_npb' => $t->nomor_npb])
            ->toArray();
    }

    public function batalHapus(): void
    {
        $this->idHapusSatuan = null;
        $this->modeHapusBulk = false;
        $this->modalHapusTampil = false;
        $this->transaksiTerpengaruh = [];
    }

    public function eksekusiHapus(): void
    {
        $sekolahId = auth()->user()->sekolah_id;

        if ($this->modeHapusBulk) {
            $jumlah = Transaksi::where('sekolah_id', $sekolahId)->whereIn('id', $this->selected)->count();
            // items ikut kehapus via cascade, otomatis ngefek ke ledger (live-computed)
            Transaksi::where('sekolah_id', $sekolahId)->whereIn('id', $this->selected)->delete();
            session()->flash('success', "{$jumlah} transaksi berhasil dihapus.");
            $this->selected = [];
        } else {
            $transaksi = Transaksi::where('sekolah_id', $sekolahId)->findOrFail($this->idHapusSatuan);
            $transaksi->delete();
            session()->flash('success', 'Transaksi berhasil dihapus.');
        }

        $this->batalHapus();
    }

    // ===== Batalkan nomor surat (satuan & bulk) — buat kasus salah generate
    // (kode sekolah/nomor urut yang keliru pas surat itu dibuat) =====
    public ?int $idBatalkanNomor = null;
    public bool $modeBatalkanNomorBulk = false;
    public bool $modalBatalkanNomorTampil = false;
    public bool $nomorBisaDikembalikan = false; // dipakai mode satuan
    public string $nomorNpbAkanDibatalkan = ''; // dipakai mode satuan
    public array $ringkasanBatalkanNomorBulk = ['bisa' => [], 'tidak_bisa' => []]; // dipakai mode bulk

    public function mintaBatalkanNomor(int $id): void
    {
        $transaksi = Transaksi::where('sekolah_id', auth()->user()->sekolah_id)
            ->with('tahunAnggaran')
            ->findOrFail($id);

        if (!$transaksi->nomor_npb) {
            return;
        }

        $urutTransaksiIni = (int) explode('/', $transaksi->nomor_npb)[1];

        $this->idBatalkanNomor = $id;
        $this->modeBatalkanNomorBulk = false;
        $this->nomorNpbAkanDibatalkan = $transaksi->nomor_npb;
        // Cuma boleh "dikembalikan" ke antrian urut kalau ini beneran nomor
        // TERAKHIR yang dikeluarin di tahun anggaran itu — kalau udah ada surat
        // lain sesudahnya, jangan disentuh sama sekali (hindari nomor bentrok).
        $this->nomorBisaDikembalikan = $urutTransaksiIni === (int) $transaksi->tahunAnggaran->nomor_urut_terakhir;
        $this->modalBatalkanNomorTampil = true;
    }

    public function mintaBatalkanNomorBulk(): void
    {
        if (empty($this->selected)) {
            return;
        }

        $sekolahId = auth()->user()->sekolah_id;
        $transaksiList = Transaksi::where('sekolah_id', $sekolahId)->whereIn('id', $this->selected)->whereNotNull('nomor_npb')->with('tahunAnggaran')->get();

        if ($transaksiList->isEmpty()) {
            $this->errorMsg = 'Transaksi yang dipilih belum ada yang punya nomor surat.';
            return;
        }

        $this->ringkasanBatalkanNomorBulk = $this->simulasikanBatalkanNomorBulk($transaksiList);
        $this->modeBatalkanNomorBulk = true;
        $this->modalBatalkanNomorTampil = true;
    }

    /**
     * Simulasi urutan reclaim nomor TANPA nulis ke DB — buat preview di modal
     * konfirmasi sebelum user beneran eksekusi. Diproses per tahun anggaran
     * (jaga-jaga kalau yang di-select ada dari tahun anggaran beda), dan
     * dalam tiap grup diurutkan dari nomor TERBESAR ke TERKECIL biar reclaim-nya
     * maksimal (misal 3 nomor terakhir yang dibatalkan sekaligus, semuanya bisa
     * kereclaim beruntun, bukan cuma yang paling besar doang).
     */
    protected function simulasikanBatalkanNomorBulk(\Illuminate\Support\Collection $transaksiList): array
    {
        $bisa = [];
        $tidakBisa = [];

        foreach ($transaksiList->groupBy('tahun_anggaran_id') as $grup) {
            $counterSimulasi = (int) $grup->first()->tahunAnggaran->nomor_urut_terakhir;
            $terurut = $grup->sortByDesc(fn($t) => (int) explode('/', $t->nomor_npb)[1]);

            foreach ($terurut as $t) {
                $urut = (int) explode('/', $t->nomor_npb)[1];
                if ($urut === $counterSimulasi) {
                    $bisa[] = $t->nomor_npb;
                    $counterSimulasi--;
                } else {
                    $tidakBisa[] = $t->nomor_npb;
                }
            }
        }

        return ['bisa' => $bisa, 'tidak_bisa' => $tidakBisa];
    }

    public function batalBatalkanNomor(): void
    {
        $this->idBatalkanNomor = null;
        $this->modeBatalkanNomorBulk = false;
        $this->nomorNpbAkanDibatalkan = '';
        $this->nomorBisaDikembalikan = false;
        $this->ringkasanBatalkanNomorBulk = ['bisa' => [], 'tidak_bisa' => []];
        $this->modalBatalkanNomorTampil = false;
    }

    public function eksekusiBatalkanNomor(): void
    {
        $sekolahId = auth()->user()->sekolah_id;

        if ($this->modeBatalkanNomorBulk) {
            $transaksiList = Transaksi::where('sekolah_id', $sekolahId)->whereIn('id', $this->selected)->whereNotNull('nomor_npb')->with('tahunAnggaran')->get();

            foreach ($transaksiList->groupBy('tahun_anggaran_id') as $grup) {
                $tahunAnggaran = $grup->first()->tahunAnggaran;
                $terurut = $grup->sortByDesc(fn($t) => (int) explode('/', $t->nomor_npb)[1]);

                foreach ($terurut as $t) {
                    $urut = (int) explode('/', $t->nomor_npb)[1];
                    if ($urut === (int) $tahunAnggaran->nomor_urut_terakhir) {
                        $tahunAnggaran->decrement('nomor_urut_terakhir');
                        $tahunAnggaran->refresh();
                    }
                }
            }

            Transaksi::where('sekolah_id', $sekolahId)
                ->whereIn('id', $this->selected)
                ->whereNotNull('nomor_npb')
                ->update(['nomor_npb' => null, 'nomor_spb' => null, 'nomor_sppb' => null, 'tanggal_spb' => null, 'tanggal_sppb' => null, 'status' => 'draft']);

            session()->flash('success', 'Nomor surat terpilih berhasil dibatalkan.');
            $this->selected = [];
        } else {
            $transaksi = Transaksi::where('sekolah_id', $sekolahId)->with('tahunAnggaran')->findOrFail($this->idBatalkanNomor);

            if ($transaksi->nomor_npb) {
                $urutTransaksiIni = (int) explode('/', $transaksi->nomor_npb)[1];

                if ($urutTransaksiIni === (int) $transaksi->tahunAnggaran->nomor_urut_terakhir) {
                    $transaksi->tahunAnggaran->decrement('nomor_urut_terakhir');
                }
            }

            $transaksi->update(['nomor_npb' => null, 'nomor_spb' => null, 'nomor_sppb' => null, 'tanggal_spb' => null, 'tanggal_sppb' => null, 'status' => 'draft']);
            session()->flash('success', 'Nomor surat berhasil dibatalkan. Generate ulang buat dapat nomor baru.');
        }

        $this->batalBatalkanNomor();
    }

    public function generate(int $transaksiId, string $format, NomorSuratService $nomorService, SuratWordGenerator $wordGenerator, SuratPdfGenerator $pdfGenerator)
    {
        $this->errorMsg = null;

        $transaksi = Transaksi::where('sekolah_id', auth()->user()->sekolah_id)
            ->with('sekolah', 'tahunAnggaran')
            ->findOrFail($transaksiId);

        if (!$transaksi->pihak_peminta_id) {
            $this->errorMsg = 'Pihak yang meminta belum dipilih. Lengkapi dulu sebelum generate surat.';
            return;
        }

        if (!$transaksi->nomor_npb) {
            $nomorNpb = $nomorService->generateNomorNpb($transaksi->sekolah, $transaksi->tahunAnggaran, $transaksi->tanggal_npb);

            $transaksi->update([
                'nomor_npb' => $nomorNpb,
                'nomor_spb' => $nomorService->turunanSpb($nomorNpb),
                'nomor_sppb' => $nomorService->turunanSppb($nomorNpb),
                'tanggal_spb' => $transaksi->tanggal_npb,
                'tanggal_sppb' => $transaksi->tanggal_npb,
            ]);
            $transaksi->refresh();
        }

        $path = $format === 'pdf' ? $pdfGenerator->generate($transaksi) : $wordGenerator->generate($transaksi);

        if ($transaksi->status !== 'selesai') {
            $transaksi->update(['status' => 'selesai']);
        }

        return response()->download($path)->deleteFileAfterSend(true);
    }

    public function generateBulk(string $format, NomorSuratService $nomorService, SuratWordGenerator $wordGenerator, SuratPdfGenerator $pdfGenerator)
    {
        $this->errorMsg = null;

        if (empty($this->selected)) {
            $this->errorMsg = 'Pilih minimal 1 transaksi untuk generate bulk.';
            return;
        }

        $transaksiList = Transaksi::where('sekolah_id', auth()->user()->sekolah_id)
            ->whereIn('id', $this->selected)
            ->with('sekolah', 'tahunAnggaran')
            // Nomor urut surat harus ngikutin kronologis tanggal transaksi, BUKAN
            // urutan klik/pilih/halaman user pas nge-select checkbox. generateNomorNpb()
            // cuma nge-increment counter polos sesuai urutan dipanggil, jadi urutan
            // query di sini yang nentuin urutan penomoran akhirnya.
            ->orderBy('tanggal_npb')
            ->orderBy('id')
            ->get();

        $belumMapping = $transaksiList->filter(fn($t) => !$t->pihak_peminta_id);
        if ($belumMapping->isNotEmpty()) {
            $this->errorMsg = 'Ada transaksi yang pihak pemintanya belum dipilih: ' . $belumMapping->pluck('nomor_referensi_asal')->implode(', ');
            return;
        }

        $paths = [];
        foreach ($transaksiList as $transaksi) {
            if (!$transaksi->nomor_npb) {
                $nomorNpb = $nomorService->generateNomorNpb($transaksi->sekolah, $transaksi->tahunAnggaran, $transaksi->tanggal_npb);
                $transaksi->update([
                    'nomor_npb' => $nomorNpb,
                    'nomor_spb' => $nomorService->turunanSpb($nomorNpb),
                    'nomor_sppb' => $nomorService->turunanSppb($nomorNpb),
                    'tanggal_spb' => $transaksi->tanggal_npb,
                    'tanggal_sppb' => $transaksi->tanggal_npb,
                ]);
                $transaksi->refresh();
            }

            $paths[] = $format === 'pdf' ? $pdfGenerator->generate($transaksi) : $wordGenerator->generate($transaksi);

            if ($transaksi->status !== 'selesai') {
                $transaksi->update(['status' => 'selesai']);
            }
        }

        $zipPath = storage_path('app/generated/bulk_surat_' . now()->timestamp . '.zip');
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE);
        foreach ($paths as $path) {
            $zip->addFile($path, basename($path));
        }
        $zip->close();
        foreach ($paths as $path) {
            @unlink($path);
        }

        $this->selected = [];

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }

    public function with(PersediaanService $service): array
    {
        $sekolahId = auth()->user()->sekolah_id;

        $query = Transaksi::where('sekolah_id', $sekolahId)->when($this->search, fn($q) => $q->where('nomor_referensi_asal', 'like', "%{$this->search}%")->orWhere('nomor_npb', 'like', "%{$this->search}%"))->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))->withCount('items')->with('items.masterBarang', 'pihakPeminta');

        $daftarTransaksi = $query->orderBy($this->sortBy, $this->sortDir)->paginate($this->perPage === 'semua' ? 100000 : (int) $this->perPage);

        foreach ($daftarTransaksi as $t) {
            if ($t->status === 'draft' && !array_key_exists($t->id, $this->pihakPeminta)) {
                $this->pihakPeminta[$t->id] = $t->pihak_peminta_id;
            }

            foreach ($t->items as $item) {
                $item->sisa_sebelum = $service->sisaSebelumTransaksi($item->master_barang_id, $t);
            }
        }

        return [
            'daftarTransaksi' => $daftarTransaksi,
            'daftarPegawai' => Pegawai::where('sekolah_id', $sekolahId)->orderBy('nama')->get(),
            'jumlahDraft' => Transaksi::where('sekolah_id', $sekolahId)->where('status', 'draft')->count(),
            'jumlahSelesai' => Transaksi::where('sekolah_id', $sekolahId)->where('status', 'selesai')->count(),
        ];
    }
}; ?>

<div>
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900">Transaksi Keluar</h1>
            <p class="text-sm text-zinc-500 mt-1">Kelola permintaan barang & generate surat.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('transaksi.create') }}" wire:navigate>
                <x-secondary-button>+ Tambah Manual</x-secondary-button>
            </a>
            <a href="{{ route('transaksi.upload') }}" wire:navigate>
                <x-primary-button>+ Upload Excel</x-primary-button>
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-4 p-3 bg-emerald-50 text-emerald-700 rounded-lg text-sm border border-emerald-100">
            {{ session('success') }}</div>
    @endif
    @if ($errorMsg)
        <div class="mb-4 p-3 bg-red-50 text-red-700 rounded-lg text-sm border border-red-100">{{ $errorMsg }}</div>
    @endif

    <div class="mb-4 flex flex-col sm:flex-row gap-3">
        <div class="relative max-w-sm flex-1">
            <svg class="w-4 h-4 text-zinc-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none"
                stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input wire:model.live.debounce.300ms="search" type="text"
                placeholder="Cari no. referensi/nomor surat..."
                class="w-full pl-10 pr-4 py-2.5 border-zinc-200 rounded-lg shadow-sm text-sm focus:border-emerald-500 focus:ring-emerald-500">
        </div>
        <select wire:model.live="filterStatus"
            class="w-full sm:w-56 py-2.5 border-zinc-200 rounded-lg shadow-sm text-sm focus:border-emerald-500 focus:ring-emerald-500">
            <option value="">Semua Status</option>
            <option value="draft">Draft ({{ $jumlahDraft }})</option>
            <option value="selesai">Selesai ({{ $jumlahSelesai }})</option>
        </select>
        <x-per-page-selector />
    </div>

    @if (count($selected) > 0)
        <div class="mb-3 p-3 bg-zinc-100 rounded-lg flex items-center justify-between">
            <span class="text-sm text-zinc-700">{{ count($selected) }} transaksi dipilih</span>
            <div class="space-x-2">
                <button wire:click="generateBulk('docx')" wire:loading.attr="disabled"
                    class="px-3 py-1.5 bg-zinc-800 text-white text-xs rounded-lg hover:bg-zinc-700">Generate Word
                    (ZIP)</button>
                <button wire:click="generateBulk('pdf')" wire:loading.attr="disabled"
                    class="px-3 py-1.5 bg-zinc-600 text-white text-xs rounded-lg hover:bg-zinc-500">Generate PDF
                    (ZIP)</button>
                <button wire:click="mintaHapusBulk"
                    class="px-3 py-1.5 bg-red-600 text-white text-xs font-medium rounded-lg hover:bg-red-500">Hapus
                    Terpilih</button>
                <button wire:click="mintaBatalkanNomorBulk"
                    class="px-3 py-1.5 bg-amber-600 text-white text-xs font-medium rounded-lg hover:bg-amber-500">Batalkan
                    Nomor Terpilih</button>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-xl border border-zinc-100 shadow-sm overflow-visible">
        <table class="min-w-full divide-y divide-zinc-100">
            <thead class="bg-zinc-50">
                <tr>
                    <th class="pl-4 pr-2 py-3 w-10">
                        <input type="checkbox" class="rounded"
                            wire:click="$set('selected', $event.target.checked ? {{ $daftarTransaksi->pluck('id') }} : [])">
                    </th>
                    <x-th-sortable column="nomor_referensi_asal" :sortBy="$sortBy" :sortDir="$sortDir">No.
                        Referensi</x-th-sortable>
                    <x-th-sortable column="nomor_npb" :sortBy="$sortBy" :sortDir="$sortDir">Nomor Surat</x-th-sortable>
                    <x-th-sortable column="tanggal_npb" :sortBy="$sortBy" :sortDir="$sortDir">Tanggal</x-th-sortable>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 uppercase tracking-wider">Rincian
                        Barang</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 uppercase tracking-wider">Pihak
                        Meminta</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 uppercase tracking-wider">Status
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-zinc-500 uppercase tracking-wider">Aksi
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse ($daftarTransaksi as $t)
                    <tr wire:key="transaksi-{{ $t->id }}" class="hover:bg-zinc-50">
                        <td class="pl-4 pr-2 py-2"><input type="checkbox" class="rounded" wire:model.live="selected"
                                value="{{ $t->id }}">
                        </td>
                        <td class="px-4 py-3 text-sm font-medium text-zinc-900">{{ $t->nomor_referensi_asal }}</td>
                        <td class="px-4 py-3 text-sm text-zinc-500">{{ $t->nomor_npb ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-zinc-500">{{ $t->tanggal_npb->format('d-m-Y') }}</td>
                        <td class="px-4 py-3 text-sm text-zinc-600">
                            <ul class="space-y-0.5">
                                @foreach ($t->items as $item)
                                    <li>
                                        {{ $item->masterBarang->nama_barang }} — {{ $item->jumlah }}
                                        {{ $item->satuan }}
                                        @if ($item->sisa_sebelum < $item->jumlah)
                                            <a href="{{ route('persediaan.riwayat', $item->masterBarang) }}"
                                                wire:navigate class="text-red-600 text-xs underline">⚠ stok kurang, cek
                                                riwayat BPU</a>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </td>
                        <td class="px-4 py-3 text-sm w-56">
                            @if ($t->status === 'draft')
                                <x-combobox :options="$daftarPegawai->map(
                                    fn($p) => ['id' => $p->id, 'label' => $p->nama . ' (' . $p->jabatan . ')'],
                                )" :model="'pihakPeminta.' . $t->id" placeholder="Cari pegawai..." />
                            @else
                                {{ $t->pihakPeminta->nama ?? '-' }}
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <span
                                class="px-2 py-0.5 rounded-full text-xs font-medium
                                {{ $t->status === 'selesai' ? 'bg-emerald-100 text-emerald-700' : 'bg-zinc-100 text-zinc-600' }}">
                                {{ ucfirst($t->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-right">
                            <x-action-dropdown>
                                <a href="{{ route('transaksi.edit', $t) }}" wire:navigate
                                    class="block px-4 py-2 text-sm text-zinc-700 hover:bg-zinc-50">Edit</a>
                                <button wire:click="generate({{ $t->id }}, 'docx')" wire:loading.attr="disabled"
                                    wire:target="generate({{ $t->id }}, 'docx')"
                                    class="block w-full text-left px-4 py-2 text-sm text-zinc-700 hover:bg-zinc-50">Generate
                                    Word</button>
                                <button wire:click="generate({{ $t->id }}, 'pdf')" wire:loading.attr="disabled"
                                    wire:target="generate({{ $t->id }}, 'pdf')"
                                    class="block w-full text-left px-4 py-2 text-sm text-zinc-700 hover:bg-zinc-50">Generate
                                    PDF</button>
                                @if ($t->nomor_npb)
                                    <button wire:click="mintaBatalkanNomor({{ $t->id }})"
                                        class="block w-full text-left px-4 py-2 text-sm text-amber-700 hover:bg-amber-50">Batalkan
                                        Nomor</button>
                                @endif
                                <div class="border-t border-zinc-100 my-1"></div>
                                <button wire:click="mintaHapusSatuan({{ $t->id }})"
                                    class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">Hapus</button>
                            </x-action-dropdown>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-5 py-16 text-center">
                            <svg class="w-10 h-10 text-zinc-300 mx-auto mb-3" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                            <p class="text-sm text-zinc-500">
                                @if ($search || $filterStatus)
                                    Tidak ada transaksi yang cocok dengan pencarian/filter.
                                @else
                                    Belum ada data transaksi.
                                @endif
                            </p>
                            @if (!$search && !$filterStatus)
                                <a href="{{ route('transaksi.create') }}" wire:navigate
                                    class="text-sm text-emerald-600 font-medium hover:underline mt-1 inline-block">+
                                    Tambah
                                    transaksi pertama</a>
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $daftarTransaksi->links() }}</div>

    <x-modal-konfirmasi-hapus :show="$modalHapusTampil" :title="$modeHapusBulk ? 'Hapus ' . count($selected) . ' Transaksi?' : 'Hapus Transaksi Ini?'">
        @if ($modeHapusBulk)
            <p>{{ count($selected) }} transaksi yang dipilih akan dihapus. Tindakan ini tidak bisa dibatalkan.</p>
        @else
            <p>Transaksi <strong>{{ $transaksiTerpengaruh[0]['nomor_referensi_asal'] ?? '' }}</strong> akan dihapus.
                Tindakan ini
                tidak bisa dibatalkan.</p>
        @endif

        @php $punyaNomorSurat = array_filter($transaksiTerpengaruh, fn($t) => !empty($t['nomor_npb'])); @endphp
        @if (!empty($punyaNomorSurat))
            <div class="mt-3 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                <p class="text-xs font-medium text-amber-800 mb-1">⚠ Nomor surat berikut akan ikut hilang:</p>
                <ul class="text-xs text-amber-700 list-disc list-inside space-y-0.5">
                    @foreach ($punyaNomorSurat as $t)
                        <li>{{ $t['nomor_npb'] }} ({{ $t['nomor_referensi_asal'] }})</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </x-modal-konfirmasi-hapus>

    @if ($modalBatalkanNomorTampil)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-zinc-900/40" wire:click="batalBatalkanNomor"></div>

            <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md p-6"
                @keydown.escape.window="$wire.batalBatalkanNomor()">
                <div class="flex items-start gap-3 mb-1">
                    <div class="w-10 h-10 rounded-full bg-amber-50 flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" class="text-amber-600">
                            <path
                                d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z">
                            </path>
                            <line x1="12" y1="9" x2="12" y2="13"></line>
                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                        </svg>
                    </div>
                    <div class="min-w-0 pt-1.5">
                        <h3 class="font-semibold text-zinc-900">
                            {{ $modeBatalkanNomorBulk ? 'Batalkan ' . (count($ringkasanBatalkanNomorBulk['bisa']) + count($ringkasanBatalkanNomorBulk['tidak_bisa'])) . ' Nomor Surat?' : 'Batalkan Nomor Surat?' }}
                        </h3>
                    </div>
                </div>

                <div class="text-sm text-zinc-600 mt-3 pl-[52px] space-y-3">
                    @if ($modeBatalkanNomorBulk)
                        <p>Nomor surat pada transaksi yang dipilih akan dikosongkan, statusnya balik jadi
                            <strong>draft</strong>. Generate ulang buat dapat nomor baru setelah data diperbaiki.</p>

                        @if (!empty($ringkasanBatalkanNomorBulk['bisa']))
                            <div class="text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg p-2.5 text-xs">
                                <p class="font-medium mb-1">✓ {{ count($ringkasanBatalkanNomorBulk['bisa']) }} nomor
                                    bakal dikembalikan ke antrian urut:</p>
                                <ul class="list-disc list-inside space-y-0.5">
                                    @foreach ($ringkasanBatalkanNomorBulk['bisa'] as $nomor)
                                        <li>{{ $nomor }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if (!empty($ringkasanBatalkanNomorBulk['tidak_bisa']))
                            <div class="text-amber-700 bg-amber-50 border border-amber-200 rounded-lg p-2.5 text-xs">
                                <p class="font-medium mb-1">⚠ {{ count($ringkasanBatalkanNomorBulk['tidak_bisa']) }}
                                    nomor akan hilang permanen (sudah ada surat lain sesudahnya):</p>
                                <ul class="list-disc list-inside space-y-0.5">
                                    @foreach ($ringkasanBatalkanNomorBulk['tidak_bisa'] as $nomor)
                                        <li>{{ $nomor }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    @else
                        <p>Nomor <strong>{{ $nomorNpbAkanDibatalkan }}</strong> akan dikosongkan. Status transaksi
                            ini balik jadi <strong>draft</strong>, dan kamu bisa generate ulang buat dapat nomor baru
                            setelah data (kode sekolah, dsb.) diperbaiki.</p>

                        @if ($nomorBisaDikembalikan)
                            <p class="text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg p-2.5 text-xs">
                                ✓ Ini nomor <strong>terakhir</strong> yang dikeluarkan — nomor urutnya bakal
                                dikembalikan, jadi generate berikutnya bisa reuse nomor yang sama (nggak ada nomor
                                yang bolong/kelewat).
                            </p>
                        @else
                            <p class="text-amber-700 bg-amber-50 border border-amber-200 rounded-lg p-2.5 text-xs">
                                ⚠ Sudah ada surat lain yang dibuat setelah ini — nomor urutnya <strong>tidak
                                    bisa</strong> dikembalikan (demi menghindari nomor bentrok/duplikat). Nomor ini
                                akan hilang permanen, generate berikutnya dapat nomor baru yang lebih besar.
                            </p>
                        @endif
                    @endif
                </div>

                <div class="flex justify-end gap-2 mt-6">
                    <button type="button" wire:click="batalBatalkanNomor"
                        class="px-4 py-2 text-sm font-medium text-zinc-600 rounded-lg hover:bg-zinc-100">
                        Batal
                    </button>
                    <button type="button" wire:click="eksekusiBatalkanNomor"
                        class="px-4 py-2 text-sm font-medium text-white bg-amber-600 rounded-lg hover:bg-amber-500">
                        Ya, Batalkan Nomor
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>