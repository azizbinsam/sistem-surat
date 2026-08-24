@props(['active' => false, 'icon' => null])

<a {{ $attributes->merge(['class' => 'flex items-center gap-3 mx-3 px-3 py-2.5 text-sm rounded-lg transition '
    . ($active ? 'bg-emerald-600 text-white shadow-sm' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white')]) }} wire:navigate>
    @if ($icon)
        <span class="shrink-0">{{ $icon }}</span>
    @endif
    <span>{{ $slot }}</span>
</a>