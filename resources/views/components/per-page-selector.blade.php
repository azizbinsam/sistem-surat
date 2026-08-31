@props(['options' => [10, 20, 50, 'semua']])

<select wire:model.live="perPage"
    {{ $attributes->merge(['class' => 'py-2.5 border-zinc-200 rounded-lg shadow-sm text-sm focus:border-emerald-500 focus:ring-emerald-500']) }}>
    @foreach ($options as $opsi)
        <option value="{{ $opsi }}">{{ $opsi === 'semua' ? 'Tampilkan Semua' : "{$opsi} baris" }}</option>
    @endforeach
</select>
