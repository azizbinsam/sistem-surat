@props([
    'show' => false,
    'title' => 'Konfirmasi Hapus',
    'confirmMethod' => 'eksekusiHapus',
    'confirmLabel' => 'Ya, Hapus',
    'cancelMethod' => 'batalHapus',
    'loadingTarget' => null,
])

@if ($show)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-zinc-900/40" wire:click="{{ $cancelMethod }}"></div>

        <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md p-6"
            @keydown.escape.window="$wire.{{ $cancelMethod }}()">
            <div class="flex items-start gap-3 mb-1">
                <div class="w-10 h-10 rounded-full bg-red-50 flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="text-red-600">
                        <path d="M3 6h18"></path>
                        <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
                        <line x1="10" y1="11" x2="10" y2="17"></line>
                        <line x1="14" y1="11" x2="14" y2="17"></line>
                    </svg>
                </div>
                <div class="min-w-0 pt-1.5">
                    <h3 class="font-semibold text-zinc-900">{{ $title }}</h3>
                </div>
            </div>

            <div class="text-sm text-zinc-600 mt-3 pl-[52px]">
                {{ $slot }}
            </div>

            <div class="flex justify-end gap-2 mt-6">
                <button type="button" wire:click="{{ $cancelMethod }}"
                    class="px-4 py-2 text-sm font-medium text-zinc-600 rounded-lg hover:bg-zinc-100">
                    Batal
                </button>
                <button type="button" wire:click="{{ $confirmMethod }}"
                    @if ($loadingTarget) wire:loading.attr="disabled" wire:target="{{ $loadingTarget }}" @endif
                    class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-500 disabled:opacity-60">
                    {{ $confirmLabel }}
                </button>
            </div>
        </div>
    </div>
@endif
