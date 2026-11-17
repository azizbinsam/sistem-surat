@props(['tahun' => now()->year, 'aktif' => false])

{{-- Filter periode: pilih triwulan (isi tanggal otomatis) atau atur rentang tanggal manual.
     Dipakai bareng trait App\Livewire\Concerns\FiltersByPeriode. --}}
<div class="flex flex-col sm:flex-row sm:items-end gap-3">
    <div>
        <label class="block text-xs font-medium text-zinc-500 mb-1">Triwulan {{ $tahun }}</label>
        <select wire:model.live="triwulan"
            class="w-full sm:w-52 py-2.5 border-zinc-200 rounded-lg shadow-sm text-sm focus:border-emerald-500 focus:ring-emerald-500">
            <option value="">Semua triwulan</option>
            @foreach (\App\Support\Triwulan::NAMA as $nomor => $nama)
                <option value="{{ $nomor }}">{{ $nama }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs font-medium text-zinc-500 mb-1">Dari tanggal</label>
        <input type="date" wire:model.live="tanggalAwal"
            class="w-full py-2.5 border-zinc-200 rounded-lg shadow-sm text-sm focus:border-emerald-500 focus:ring-emerald-500">
    </div>
    <div>
        <label class="block text-xs font-medium text-zinc-500 mb-1">Sampai tanggal</label>
        <input type="date" wire:model.live="tanggalAkhir"
            class="w-full py-2.5 border-zinc-200 rounded-lg shadow-sm text-sm focus:border-emerald-500 focus:ring-emerald-500">
    </div>
    @if ($aktif)
        <button type="button" wire:click="resetFilterPeriode"
            class="py-2.5 px-3 text-sm text-zinc-500 hover:text-red-600 font-medium">Reset</button>
    @endif
</div>
