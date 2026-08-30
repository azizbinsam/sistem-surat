<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Volt\Component;

new class extends Component {
    public bool $tertutup = false;
    public bool $terkirimUlang = false;

    public function mount(): void
    {
        // Dismiss disimpan di session (bukan cuma state komponen), biar nggak
        // muncul lagi tiap pindah halaman selama sesi login yang sama — tapi
        // tetap muncul lagi kalau login ulang, sampai user beneran verifikasi.
        $this->tertutup = (bool) Session::get('verifikasi_banner_tertutup', false);
    }

    public function kirimUlang(): void
    {
        Auth::user()->sendEmailVerificationNotification();
        $this->terkirimUlang = true;
    }

    public function tutup(): void
    {
        $this->tertutup = true;
        Session::put('verifikasi_banner_tertutup', true);
    }
}; ?>

<div>
    @if (auth()->check() && !auth()->user()->hasVerifiedEmail() && !$tertutup)
        <div class="bg-amber-50 border-b border-amber-200 px-4 sm:px-6 lg:px-8 py-2.5">
            <div class="flex items-center justify-center gap-3 flex-wrap max-w-7xl mx-auto">
                <p class="text-sm text-amber-800 flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        class="shrink-0">
                        <path
                            d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z">
                        </path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                    @if ($terkirimUlang)
                        Link verifikasi baru sudah dikirim — cek inbox (atau folder spam) email kamu.
                    @else
                        Email kamu belum diverifikasi.
                    @endif
                </p>
                <div class="flex items-center gap-4 shrink-0">
                    @unless ($terkirimUlang)
                        <button wire:click="kirimUlang" wire:loading.attr="disabled"
                            class="text-sm font-medium text-amber-800 hover:underline disabled:opacity-60">
                            Kirim Ulang Link Verifikasi
                        </button>
                    @endunless
                    <button wire:click="tutup" type="button" class="text-amber-500 hover:text-amber-700"
                        aria-label="Tutup">✕</button>
                </div>
            </div>
        </div>
    @endif
</div>
