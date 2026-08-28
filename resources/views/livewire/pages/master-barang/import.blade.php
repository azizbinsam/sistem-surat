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

        try {
            \Illuminate\Support\Facades\DB::transaction(function () {
                Excel::import(new MasterBarangImport(auth()->user()->sekolah_id), $this->file);
            });
            session()->flash('success', 'Import berhasil.');
            $this->redirect(route('master-barang.index'), navigate: true);
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $errors = collect($e->failures())->map(fn($f) => "Baris {$f->row()}: " . implode(', ', $f->errors()))->implode(' | ');
            $this->errorMsg = $errors;
        }
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
        <h1 class="text-2xl font-bold text-zinc-900">Import Master Barang</h1>
        <p class="text-sm text-zinc-500 mt-1">Upload daftar barang sekaligus lewat file Excel.</p>
    </div>

    <div class="mb-4 p-4 bg-zinc-50 rounded-lg text-sm text-zinc-700 border border-zinc-100">
        Format kolom wajib: <strong>Kode Barang</strong>, <strong>Nama Barang</strong>, <strong>Satuan Default</strong>
        (Kategori & Spesifikasi Default opsional).
        <a href="{{ route('master-barang.template') }}" class="block mt-2 text-emerald-700 font-medium hover:underline">
            ⬇ Download Template Excel
        </a>
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

        <div class="flex items-center gap-3 pt-2">
            <x-primary-button>Import</x-primary-button>
            <a href="{{ route('master-barang.index') }}" wire:navigate
                class="text-sm text-zinc-500 hover:text-zinc-700">Batal</a>
        </div>
    </form>
</div>
