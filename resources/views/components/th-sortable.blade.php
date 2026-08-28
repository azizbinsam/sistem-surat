@props(['column', 'sortBy', 'sortDir', 'align' => 'left'])

<th wire:click="sortir('{{ $column }}')"
    class="px-4 py-2.5 text-{{ $align }} text-xs font-semibold text-zinc-500 uppercase tracking-wider cursor-pointer select-none hover:text-zinc-700">
    <span class="inline-flex items-center gap-1">
        {{ $slot }}
        @if ($sortBy === $column)
            @if ($sortDir === 'asc')
                <svg class="w-3 h-3 text-zinc-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7" />
                </svg>
            @else
                <svg class="w-3 h-3 text-zinc-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                </svg>
            @endif
        @else
            <svg class="w-3 h-3 text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4" />
            </svg>
        @endif
    </span>
</th>
