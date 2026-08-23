<?php

use App\Models\Pegawai;
use App\Models\Transaksi;
use App\Services\NomorSuratService;
use App\Services\PersediaanService;
use App\Services\SuratWordGenerator;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    public array $pihakPeminta = [];
    public ?string $errorMsg = null;

    public function updated($name, $value): void
    {
        if (Str::startsWith($name, 'pihakPeminta.')) {
            $transaksiId = (int) Str::after($name, 'pihakPeminta.');

            $transaksi = Transaksi::where('sekolah_id', auth()->user()->sekolah_id)->findOrFail($transaksiId);

            $transaksi->update(['pihak_peminta_id' => $value ?: null]);

            session()->flash('success', 'Pihak peminta berhasil diperbarui.');
        }
    }

    public function generate(int $transaksiId, string $format, NomorSuratService $nomorService, SuratWordGenerator $wordGenerator, \App\Services\SuratPdfGenerator $pdfGenerator)
    {
        $this->errorMsg = null;

        $transaksi = Transaksi::where('sekolah_id', auth()->user()->sekolah_id)
            ->with('sekolah')
            ->findOrFail($transaksiId);

        if (!$transaksi->pihak_peminta_id) {
            $this->errorMsg = 'Pihak yang meminta belum dipilih. Lengkapi dulu sebelum generate surat.';
            return;
        }

        if (!$transaksi->nomor_npb) {
            $nomorNpb = $nomorService->generateNomorNpb($transaksi->sekolah, $transaksi->tanggal_npb);

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

        $transaksi->update(['status' => 'selesai']);

        return response()->download($path)->deleteFileAfterSend(true);
    }

    public array $selected = [];

    public function generateBulk(string $format, NomorSuratService $nomorService, SuratWordGenerator $wordGenerator, \App\Services\SuratPdfGenerator $pdfGenerator)
    {
        $this->errorMsg = null;

        if (empty($this->selected)) {
            $this->errorMsg = 'Pilih minimal 1 transaksi untuk generate bulk.';
            return;
        }

        $transaksiList = Transaksi::where('sekolah_id', auth()->user()->sekolah_id)
            ->whereIn('id', $this->selected)
            ->with('sekolah')
            ->get();

        $belumMapping = $transaksiList->filter(fn($t) => !$t->pihak_peminta_id);
        if ($belumMapping->isNotEmpty()) {
            $this->errorMsg = 'Ada transaksi yang pihak pemintanya belum dipilih: ' . $belumMapping->pluck('nomor_referensi_asal')->implode(', ');
            return;
        }

        $paths = [];
        foreach ($transaksiList as $transaksi) {
            if (!$transaksi->nomor_npb) {
                $nomorNpb = $nomorService->generateNomorNpb($transaksi->sekolah, $transaksi->tanggal_npb);

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

            $transaksi->update(['status' => 'selesai']);
        }

        $zipPath = storage_path('app/generated/bulk_surat_' . now()->timestamp . '.zip');
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE);
        foreach ($paths as $path) {
            $zip->addFile($path, basename($path));
        }
        $zip->close();

        foreach ($paths as $path) {
            @unlink($path); // hapus file individual, sisain zip-nya aja
        }

        $this->selected = [];

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }

    public function with(PersediaanService $service): array
    {
        $daftarTransaksi = Transaksi::where('sekolah_id', auth()->user()->sekolah_id)
            ->where('status', 'draft')
            ->withCount('items')
            ->with('items.masterBarang')
            ->orderByDesc('tanggal_npb')
            ->paginate(10);

        foreach ($daftarTransaksi as $t) {
            if (!array_key_exists($t->id, $this->pihakPeminta)) {
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
        ];
    }
}; ?>

<div>
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-semibold text-zinc-900">Draft Transaksi (Belum Digenerate)</h2>
        <a href="{{ route('transaksi.upload') }}" wire:navigate>
            <x-primary-button>+ Upload Transaksi Keluar</x-primary-button>
        </a>
    </div>

    @if (session('success'))
        <div class="mb-4 p-3 bg-zinc-100 text-zinc-800 rounded-md text-sm">{{ session('success') }}</div>
    @endif

    @if ($errorMsg)
        <div class="mb-4 p-3 bg-red-50 text-red-700 rounded-md text-sm">{{ $errorMsg }}</div>
    @endif

    @if (count($selected) > 0)
        <div class="mb-3 p-3 bg-zinc-100 rounded-md flex items-center justify-between">
            <span class="text-sm text-zinc-700">{{ count($selected) }} transaksi dipilih</span>
            <div class="space-x-2">
                <button wire:click="generateBulk('docx')" wire:loading.attr="disabled"
                    class="px-3 py-1.5 bg-zinc-800 text-white text-xs rounded hover:bg-zinc-700">
                    Generate Word (ZIP)
                </button>
                <button wire:click="generateBulk('pdf')" wire:loading.attr="disabled"
                    class="px-3 py-1.5 bg-zinc-800 text-white text-xs rounded hover:bg-zinc-700">
                    Generate PDF (ZIP)
                </button>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-md shadow overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-zinc-50">
                <tr>
                    <th class="px-4 py-2 text-left">
                        <input type="checkbox"
                            wire:click="$set('selected', $event.target.checked ? {{ $daftarTransaksi->pluck('id') }} : [])">
                    </th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-600 uppercase">No. Referensi</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-600 uppercase">Tanggal</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-600 uppercase">Rincian Barang</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-600 uppercase">Pihak yang Meminta</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-zinc-600 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($daftarTransaksi as $t)
                    <tr wire:key="transaksi-{{ $t->id }}">
                        <td class="px-4 py-2">
                            <input type="checkbox" wire:model="selected" value="{{ $t->id }}">
                        </td>
                        <td class="px-4 py-2 text-sm font-medium">{{ $t->nomor_referensi_asal }}</td>
                        <td class="px-4 py-2 text-sm">{{ $t->tanggal_npb->format('d-m-Y') }}</td>
                        <td class="px-4 py-2 text-sm text-gray-600">
                            <ul class="space-y-0.5">
                                @foreach ($t->items as $item)
                                    <li>
                                        {{ $item->masterBarang->nama_barang }} — diminta {{ $item->jumlah }}
                                        {{ $item->satuan }},
                                        sisa sebelum transaksi:
                                        <span
                                            class="{{ $item->sisa_sebelum < $item->jumlah ? 'text-red-600 font-semibold' : 'text-zinc-700' }}">
                                            {{ $item->sisa_sebelum }}
                                        </span>
                                        @if ($item->sisa_sebelum < $item->jumlah)
                                            <span class="text-red-600 text-xs">⚠ stok tidak cukup</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </td>
                        <td class="px-4 py-2 text-sm">
                            <select wire:model.live="pihakPeminta.{{ $t->id }}"
                                class="w-full text-sm border-gray-300 rounded-md focus:border-zinc-500 focus:ring-zinc-500">
                                <option value="">-- Belum dipilih --</option>
                                @foreach ($daftarPegawai as $p)
                                    <option value="{{ $p->id }}">{{ $p->nama }} ({{ $p->jabatan }})
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td class="px-4 py-2 text-sm text-right space-x-1">
                            <button wire:click="generate({{ $t->id }}, 'docx')" wire:loading.attr="disabled"
                                wire:target="generate({{ $t->id }}, 'docx')"
                                class="px-2 py-1 bg-zinc-800 text-white text-xs rounded hover:bg-zinc-700 disabled:opacity-50">
                                Word
                            </button>
                            <button wire:click="generate({{ $t->id }}, 'pdf')" wire:loading.attr="disabled"
                                wire:target="generate({{ $t->id }}, 'pdf')"
                                class="px-2 py-1 bg-zinc-800 text-white text-xs rounded hover:bg-zinc-700 disabled:opacity-50">
                                PDF
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-sm text-gray-500">Belum ada draft
                            transaksi.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $daftarTransaksi->links() }}</div>
</div>
