@props(['target', 'pesan' => 'Sedang memproses, mohon tunggu…'])

{{-- Overlay layar penuh yang muncul SELAMA method Livewire di `target` masih berjalan.
     `target` boleh banyak method dipisah koma, contoh: target="generate,generateBulk".
     Overlay menutupi halaman, jadi sekaligus mencegah klik ganda. --}}
<div wire:loading.flex wire:target="{{ $target }}" style="display: none;" role="status" aria-live="polite"
    class="fixed inset-0 z-[60] items-center justify-center bg-zinc-900/50">
    <div class="bg-white rounded-xl shadow-xl px-6 py-5 flex items-center gap-4 max-w-sm mx-4">
        <svg class="animate-spin w-6 h-6 text-emerald-600 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none"
            viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
        </svg>
        <p class="text-sm font-medium text-zinc-800">{{ $pesan }}</p>
    </div>
</div>
