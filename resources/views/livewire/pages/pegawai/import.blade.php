<?php

use App\Imports\PegawaiImport;
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
                Excel::import(new PegawaiImport(auth()->user()->sekolah_id), $this->file);
            });
            session()->flash('success', 'Import pegawai berhasil. Jangan lupa lengkapi foto tanda tangan tiap pegawai lewat menu Edit.');
            $this->redirect(route('pegawai.index'), navigate: true);
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $errors = collect($e->failures())->map(fn($f) => "Baris {$f->row()}: " . implode(', ', $f->errors()))->implode(' | ');
            $this->errorMsg = $errors;
        }
    }
}; ?>

<div class="max-w-xl">
    <h2 class="text-lg font-semibold text-zinc-900 mb-4">Import Pegawai</h2>

    <div class="mb-4 p-4 bg-zinc-50 rounded-md text-sm text-zinc-700">
        Format kolom wajib: <strong>Nama</strong>, <strong>Jabatan</strong>, <strong>NIP</strong> (opsional),
        <strong>Kategori Pegawai</strong>
        (isi salah satu: Kepala Sekolah / Pengurus Barang Pembantu / Guru / Tendik).
        Tanda tangan tetap diupload manual per pegawai setelah import.
        <a href="{{ route('pegawai.template') }}" class="block mt-2 text-zinc-900 underline">Download Template Excel</a>
    </div>

    @if ($errorMsg)
        <div class="mb-4 p-3 bg-red-50 text-red-700 rounded-md text-sm">{{ $errorMsg }}</div>
    @endif

    <form wire:submit="import" class="space-y-4 bg-white p-6 rounded-md shadow">
        <div>
            <x-input-label for="file" value="File Excel" />
            <input type="file" wire:model="file" id="file" class="block mt-1 w-full text-sm">
            <x-input-error :messages="$errors->get('file')" class="mt-2" />
            <div wire:loading wire:target="file" class="text-xs text-zinc-500 mt-1">Mengunggah file...</div>
        </div>

        <div class="flex items-center gap-3">
            <x-primary-button>Import</x-primary-button>
            <a href="{{ route('pegawai.index') }}" wire:navigate class="text-sm text-zinc-600 hover:underline">Batal</a>
        </div>
    </form>
</div>
