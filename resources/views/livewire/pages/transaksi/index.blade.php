<?php

use App\Models\Pegawai;
use App\Models\Transaksi;
use App\Services\NomorSuratService;
use App\Services\PersediaanService;
use App\Services\SuratPdfGenerator;
use App\Services\SuratWordGenerator;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Livewire\Concerns\HasCustomPagination;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination, HasCustomPagination;

    #[Url]
    public string $tab = 'draft'; // draft | selesai | semua

    public array $pihakPeminta = [];
    public array $selected = [];
    public ?string $errorMsg = null;

    public function updatedTab(): void
    {
        $this->resetPage();
        $this->selected = [];
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

    public function hapus(int $transaksiId): void
    {
        $transaksi = Transaksi::where('sekolah_id', auth()->user()->sekolah_id)->findOrFail($transaksiId);
        $transaksi->delete(); // items ikut kehapus via cascade, otomatis ngefek ke ledger (live-computed)

        session()->flash('success', 'Transaksi berhasil dihapus.');
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
        $query = Transaksi::where('sekolah_id', auth()->user()->sekolah_id)
            ->withCount('items')
            ->with('items.masterBarang', 'pihakPeminta');

        if ($this->tab === 'draft') {
            $query->where('status', 'draft');
        } elseif ($this->tab === 'selesai') {
            $query->where('status', 'selesai');
        }

        $daftarTransaksi = $query->orderByDesc('tanggal_npb')->paginate(10);

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
            'daftarPegawai' => Pegawai::where('sekolah_id', auth()->user()->sekolah_id)
                ->orderBy('nama')
                ->get(),
            'jumlahDraft' => Transaksi::where('sekolah_id', auth()->user()->sekolah_id)
                ->where('status', 'draft')
                ->count(),
            'jumlahSelesai' => Transaksi::where('sekolah_id', auth()->user()->sekolah_id)
                ->where('status', 'selesai')
                ->count(),
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

    {{-- TAB FILTER --}}
    <div class="flex gap-1 mb-4 border-b border-zinc-200">
        <button wire:click="$set('tab', 'draft')"
            class="px-4 py-2 text-sm font-medium border-b-2 -mb-px {{ $tab === 'draft' ? 'border-emerald-600 text-emerald-600' : 'border-transparent text-zinc-500 hover:text-zinc-700' }}">
            Draft ({{ $jumlahDraft }})
        </button>
        <button wire:click="$set('tab', 'selesai')"
            class="px-4 py-2 text-sm font-medium border-b-2 -mb-px {{ $tab === 'selesai' ? 'border-emerald-600 text-emerald-600' : 'border-transparent text-zinc-500 hover:text-zinc-700' }}">
            Selesai ({{ $jumlahSelesai }})
        </button>
        <button wire:click="$set('tab', 'semua')"
            class="px-4 py-2 text-sm font-medium border-b-2 -mb-px {{ $tab === 'semua' ? 'border-emerald-600 text-emerald-600' : 'border-transparent text-zinc-500 hover:text-zinc-700' }}">
            Semua
        </button>
    </div>

    @if ($tab === 'draft' && count($selected) > 0)
        <div class="mb-3 p-3 bg-zinc-100 rounded-lg flex items-center justify-between">
            <span class="text-sm text-zinc-700">{{ count($selected) }} transaksi dipilih</span>
            <div class="space-x-2">
                <button wire:click="generateBulk('docx')" wire:loading.attr="disabled"
                    class="px-3 py-1.5 bg-zinc-800 text-white text-xs rounded-lg hover:bg-zinc-700">Generate Word
                    (ZIP)</button>
                <button wire:click="generateBulk('pdf')" wire:loading.attr="disabled"
                    class="px-3 py-1.5 bg-zinc-600 text-white text-xs rounded-lg hover:bg-zinc-500">Generate PDF
                    (ZIP)</button>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-xl border border-zinc-100 shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-zinc-100">
            <thead class="bg-zinc-50">
                <tr>
                    @if ($tab === 'draft')
                        <th class="px-4 py-3">
                            <input type="checkbox"
                                wire:click="$set('selected', $event.target.checked ? {{ $daftarTransaksi->pluck('id') }} : [])">
                        </th>
                    @endif
                    <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 uppercase tracking-wider">No.
                        Referensi</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 uppercase tracking-wider">Nomor
                        Surat</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 uppercase tracking-wider">Tanggal
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 uppercase tracking-wider">Rincian
                        Barang</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 uppercase tracking-wider">Pihak
                        Meminta</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-zinc-500 uppercase tracking-wider">Aksi
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse ($daftarTransaksi as $t)
                    <tr wire:key="transaksi-{{ $t->id }}" class="hover:bg-zinc-50">
                        @if ($tab === 'draft')
                            <td class="px-4 py-3"><input type="checkbox" wire:model="selected"
                                    value="{{ $t->id }}"></td>
                        @endif
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
                        <td class="px-4 py-3 text-sm">
                            @if ($tab === 'draft')
                                <x-combobox :options="$daftarPegawai->map(
                                    fn($p) => ['id' => $p->id, 'label' => $p->nama . ' (' . $p->jabatan . ')'],
                                )" :model="'pihakPeminta.' . $t->id" placeholder="Cari pegawai..." />
                            @else
                                {{ $t->pihakPeminta->nama ?? '-' }}
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-right space-x-1 whitespace-nowrap">
                            @if ($t->status === 'draft')
                                <button wire:click="generate({{ $t->id }}, 'docx')" wire:loading.attr="disabled"
                                    class="px-2 py-1 bg-zinc-800 text-white text-xs rounded-lg hover:bg-zinc-700">Word</button>
                                <button wire:click="generate({{ $t->id }}, 'pdf')" wire:loading.attr="disabled"
                                    class="px-2 py-1 bg-zinc-600 text-white text-xs rounded-lg hover:bg-zinc-500">PDF</button>
                            @else
                                <a href="{{ route('transaksi.edit', $t) }}" wire:navigate
                                    class="text-zinc-500 hover:text-emerald-600 font-medium">Edit</a>
                                <button wire:click="generate({{ $t->id }}, 'docx')" wire:loading.attr="disabled"
                                    class="text-zinc-500 hover:text-emerald-600 font-medium">Word</button>
                                <button wire:click="generate({{ $t->id }}, 'pdf')" wire:loading.attr="disabled"
                                    class="text-zinc-500 hover:text-emerald-600 font-medium">PDF</button>
                                <button wire:click="hapus({{ $t->id }})"
                                    wire:confirm="Yakin hapus transaksi ini? Nomor surat {{ $t->nomor_npb }} akan hilang."
                                    class="text-red-500 hover:text-red-700 font-medium">Hapus</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-16 text-center text-sm text-zinc-500">
                            @if ($tab === 'draft')
                                Belum ada draft transaksi.
                            @elseif ($tab === 'selesai')
                                Belum ada transaksi yang selesai digenerate.
                            @else
                                Belum ada data transaksi.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $daftarTransaksi->links() }}</div>
</div>