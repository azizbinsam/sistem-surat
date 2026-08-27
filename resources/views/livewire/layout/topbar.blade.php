<?php

use App\Livewire\Actions\Logout;
use App\Services\TahunAnggaranResolver;
use Livewire\Volt\Component;

new class extends Component {
    public function pilihTahunAnggaran(int $tahunAnggaranId, TahunAnggaranResolver $resolver): void
    {
        $sekolah = auth()->user()->sekolah;
        $tahunAnggaran = $sekolah->tahunAnggaran()->findOrFail($tahunAnggaranId);

        $resolver->pilih($tahunAnggaran, $sekolah);

        // Data di halaman manapun berubah total begitu tahun anggaran pindah -> aman
        // & paling jelas buat user kalau diarahkan balik ke dashboard, bukan nyoba
        // "refresh di tempat" (bisa nyisain data form/halaman yang scoped ke tahun lama).
        $this->redirect(route('dashboard'), navigate: true);
    }

    public function logout(Logout $logout): void
    {
        $logout();
        $this->redirect('/', navigate: true);
    }

    public function with(TahunAnggaranResolver $resolver): array
    {
        $sekolah = auth()->user()->sekolah;

        // User yang baru daftar & belum lengkapi profil (belum punya sekolah_id) tetap
        // ngelewatin layout yang sama (halaman onboarding) -> jangan sampai crash di sini.
        if (!$sekolah) {
            return [
                'daftarTahunAnggaran' => collect(),
                'tahunAnggaranAktif' => null,
            ];
        }

        return [
            'daftarTahunAnggaran' => $sekolah->tahunAnggaran()->orderByDesc('tahun')->get(),
            'tahunAnggaranAktif' => $resolver->aktif($sekolah),
        ];
    }
}; ?>

<div class="flex items-center gap-2">
    {{-- Dropdown Tahun Anggaran --}}
    <div x-data="{ open: false }" @click.away="open = false" class="relative">
        <button @click="open = !open"
            class="flex items-center gap-1.5 text-sm font-medium text-zinc-700 border border-zinc-200 rounded-lg px-3 py-1.5 hover:bg-zinc-50">
            <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            {{ $tahunAnggaranAktif?->tahun ?? '-' }}
            <svg class="w-3.5 h-3.5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>
        <div x-show="open" x-cloak
            class="absolute right-0 mt-1 w-40 bg-white border border-zinc-200 rounded-lg shadow-lg z-40 py-1">
            <div class="px-3 py-1.5 text-xs font-semibold text-zinc-400 uppercase tracking-wide">Tahun Anggaran</div>
            @forelse ($daftarTahunAnggaran as $ta)
                <button wire:click="pilihTahunAnggaran({{ $ta->id }})" @click="open = false"
                    class="w-full text-left px-3 py-2 text-sm flex items-center justify-between hover:bg-zinc-50 {{ $tahunAnggaranAktif && $ta->id === $tahunAnggaranAktif->id ? 'font-semibold text-emerald-700' : 'text-zinc-700' }}">
                    {{ $ta->tahun }}
                    @if ($tahunAnggaranAktif && $ta->id === $tahunAnggaranAktif->id)
                        <span class="text-emerald-600">●</span>
                    @endif
                </button>
            @empty
                <p class="px-3 py-2 text-sm text-zinc-400">Belum ada tahun anggaran.</p>
            @endforelse
        </div>
    </div>

    {{-- Dropdown Profil --}}
    <div x-data="{ open: false }" @click.away="open = false" class="relative">
        <button @click="open = !open" class="flex items-center gap-2 text-sm rounded-lg pl-2 pr-1 py-1.5 hover:bg-zinc-50">
            <div class="w-7 h-7 rounded-full bg-zinc-800 flex items-center justify-center text-xs font-semibold text-white shrink-0">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>
            <span class="hidden sm:block text-zinc-700 font-medium max-w-[10rem] truncate">{{ auth()->user()->name }}</span>
            <svg class="w-3.5 h-3.5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>
        <div x-show="open" x-cloak
            class="absolute right-0 mt-1 w-56 bg-white border border-zinc-200 rounded-lg shadow-lg z-40 py-1">
            <div class="px-3 py-2 border-b border-zinc-100">
                <p class="text-sm text-zinc-900 truncate">{{ auth()->user()->name }}</p>
                <p class="text-xs text-zinc-500 truncate">{{ auth()->user()->email }}</p>
            </div>
            <a href="{{ route('pengaturan.sekolah') }}" wire:navigate @click="open = false"
                class="block px-3 py-2 text-sm text-zinc-700 hover:bg-zinc-50">Pengaturan Sekolah</a>
            <button wire:click="logout" class="w-full text-left px-3 py-2 text-sm text-red-600 hover:bg-zinc-50">Log Out</button>
        </div>
    </div>
</div>