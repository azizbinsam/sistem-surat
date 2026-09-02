@props(['align' => 'right'])

<div x-data="{ open: false }" @click.outside="open = false" class="relative inline-block text-left">
    <button type="button" @click="open = !open"
        class="p-1.5 rounded-lg text-zinc-400 hover:text-zinc-600 hover:bg-zinc-100 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="1"></circle>
            <circle cx="12" cy="5" r="1"></circle>
            <circle cx="12" cy="19" r="1"></circle>
        </svg>
    </button>

    <div x-show="open" x-transition @click="open = false"
        class="absolute {{ $align === 'right' ? 'right-0' : 'left-0' }} z-30 mt-1 w-48 bg-white border border-zinc-200 rounded-lg shadow-lg py-1"
        style="display: none;">
        {{ $slot }}
    </div>
</div>
