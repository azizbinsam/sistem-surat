<?php

use Livewire\Volt\Component;

new class extends Component {
    //
}; ?>

<div>
    {{-- overlay mobile --}}
    <div x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false"
        class="fixed inset-0 bg-zinc-900/50 z-40 lg:hidden" style="display: none;"></div>

    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        class="fixed inset-y-0 left-0 z-50 w-64 bg-zinc-900 text-zinc-300 flex flex-col transform transition-transform duration-200 ease-in-out lg:translate-x-0">
        <div class="h-16 flex items-center px-6 border-b border-zinc-800 shrink-0">
            <a href="{{ route('dashboard') }}" wire:navigate class="text-white font-bold text-lg">
                {{ config('app.name') }}
            </a>
            <button @click="sidebarOpen = false" class="ml-auto text-zinc-400 lg:hidden">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto py-4">
            <x-sidebar-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                <x-slot name="icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                </x-slot>
                Dashboard
            </x-sidebar-link>

            <div class="px-6 pt-4 pb-1 text-xs font-semibold text-zinc-500 uppercase tracking-wider">Master Data</div>
            <x-sidebar-link :href="route('master-barang.index')" :active="request()->routeIs('master-barang.*')">
                <x-slot name="icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </x-slot>
                Master Barang
            </x-sidebar-link>
            <x-sidebar-link :href="route('pegawai.index')" :active="request()->routeIs('pegawai.*')">
                <x-slot name="icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1a4 4 0 100-8 4 4 0 000 8zm6 3a4 4 0 00-3-3.87" />
                    </svg>
                </x-slot>
                Pegawai
            </x-sidebar-link>

            <div class="px-6 pt-4 pb-1 text-xs font-semibold text-zinc-500 uppercase tracking-wider">Transaksi</div>
            <x-sidebar-link :href="route('barang-masuk.index')" :active="request()->routeIs('barang-masuk.*')">
                <x-slot name="icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4v12m0 0l-4-4m4 4l4-4M4 20h16" />
                    </svg>
                </x-slot>
                Penerimaan Barang
            </x-sidebar-link>
            <x-sidebar-link :href="route('transaksi.index')" :active="request()->routeIs('transaksi.*')">
                <x-slot name="icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 20V8m0 0l-4 4m4-4l4 4M4 4h16" />
                    </svg>
                </x-slot>
                Transaksi Keluar
            </x-sidebar-link>

            <div class="px-6 pt-4 pb-1 text-xs font-semibold text-zinc-500 uppercase tracking-wider">Lainnya</div>
            <x-sidebar-link :href="route('persediaan.index')" :active="request()->routeIs('persediaan.*')">
                <x-slot name="icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm6 0V9a2 2 0 012-2h2a2 2 0 012 2v10a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </x-slot>
                Persediaan
            </x-sidebar-link>
            <x-sidebar-link :href="route('pengaturan.sekolah')" :active="request()->routeIs('pengaturan.sekolah')">
                <x-slot name="icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </x-slot>
                Pengaturan Sekolah
            </x-sidebar-link>
        </nav>

        <div class="border-t border-zinc-800 p-4 shrink-0">
            <div class="flex items-center gap-3 px-2 py-2">
                <div
                    class="w-8 h-8 rounded-full bg-zinc-700 flex items-center justify-center text-xs font-semibold text-white shrink-0">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm text-white truncate">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-zinc-500 truncate">{{ auth()->user()->email }}</p>
                </div>
            </div>
        </div>
    </aside>
</div>
