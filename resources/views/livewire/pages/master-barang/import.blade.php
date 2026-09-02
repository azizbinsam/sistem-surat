<?php

use App\Imports\MasterBarangImport;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads;

    public $file;
    public ?string $errorMsg = null;

    public function import(): void
    {
        $this->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120'],
        ]);

        $this->errorMsg = null;

        // Sengaja TANPA DB::transaction() -- kita mau baris yang valid tetap kesimpan
        // meskipun ada baris lain yang di-skip (kode barang udah ada) atau gagal.
        // Duplikat sekarang di-skip di dalam MasterBarangImport::model(), bukan lagi
        // bikin whole-file gagal; ValidationException di sini cuma buat baris yang
        // beneran nggak lengkap (kode/nama/satuan kosong).
        $importer = new MasterBarangImport(auth()->user()->sekolah_id);

        try {
            Excel::import($importer, $this->file);
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $this->errorMsg = collect($e->failures())->map(fn($f) => "Baris {$f->row()}: " . implode(', ', $f->errors()))->implode(' | ');
            return;
        }

        $pesan = "{$importer->jumlahDitambahkan()} barang berhasil ditambahkan.";

        if (!empty($importer->dilewati())) {
            $pesan .= ' ⚠ ' . count($importer->dilewati()) . ' barang dilewati karena kodenya sudah terdaftar (' . implode(', ', $importer->dilewati()) . ').';
        }

        session()->flash('success', $pesan);
        $this->redirect(route('master-barang.index'), navigate: true);
    }
}; ?>

<div class="max-w-xl">
    <div class="mb-6">
        <a href="{{ route('master-barang.index') }}" wire:navigate
            class="text-sm text-zinc-500 hover:text-zinc-700 inline-flex items-center gap-1 mb-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Kembali
        </a>

        <h1 class="text-2xl font-bold text-zinc-900">
            Import Master Barang
        </h1>

        <p class="text-sm text-zinc-500 mt-1">
            Upload daftar barang sekaligus lewat file Excel.
        </p>
    </div>

    <div class="mb-4 flex items-start gap-3 p-4 bg-zinc-50 rounded-lg border border-zinc-100">

        <svg class="w-5 h-5 mt-0.5 flex-shrink-0 text-zinc-500" fill="none" stroke="currentColor"
            viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M13 16h-1v-4h-1m1-4h.01M12 21a9 9 0 100-18 9 9 0 000 18z" />
        </svg>

        <div class="text-sm text-zinc-600">
            <p>
                Kolom wajib:
                <strong class="text-zinc-800">Kode Barang</strong>,
                <strong class="text-zinc-800">Nama Barang</strong>, dan
                <strong class="text-zinc-800">Satuan Default</strong>.
                Kategori dan Spesifikasi Default bersifat opsional.
            </p>

            <a href="{{ route('master-barang.template') }}"
                class="inline-flex items-center gap-1.5 mt-2 text-emerald-700 font-medium hover:text-emerald-800 hover:underline">

                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 3v12m0 0l-4-4m4 4l4-4M5 21h14" />
                </svg>

                Download Template Excel
            </a>
        </div>

    </div>

    @if ($errorMsg)
        <div class="mb-4 p-3 bg-red-50 text-red-700 rounded-lg text-sm border border-red-100">{{ $errorMsg }}</div>
    @endif

    <form wire:submit="import" class="space-y-4 bg-white p-6 rounded-xl border border-zinc-100 shadow-sm">
        <div>
            <x-input-label for="file" value="File Excel" />
            <input type="file" wire:model="file" id="file"
                class="block mt-1 w-full text-sm text-zinc-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-zinc-100 file:text-zinc-700 hover:file:bg-zinc-200">
            <x-input-error :messages="$errors->get('file')" class="mt-2" />
            <div wire:loading wire:target="file" class="text-xs text-zinc-500 mt-1">Mengunggah file...</div>
        </div>

        <div class="flex items-center gap-2 pt-2">
            <x-primary-button>Import</x-primary-button>
            <a href="{{ route('master-barang.index') }}" wire:navigate
                class="px-4 py-2 text-sm font-medium text-zinc-600 rounded-lg hover:bg-zinc-100">Batal</a>
        </div>
    </form>
</div>
