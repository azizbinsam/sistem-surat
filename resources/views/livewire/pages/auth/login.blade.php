<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component {
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <div class="mb-6">
        <h1 class="text-xl font-bold text-zinc-900">Selamat Datang Kembali</h1>
        <p class="text-sm text-zinc-500 mt-1">Masuk ke akun kamu untuk lanjut kelola surat sekolah.</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="login" class="space-y-5">
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="form.email" id="email" class="block mt-1 w-full" type="email" name="email"
                required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('form.email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input wire:model="form.password" id="password" class="block mt-1 w-full" type="password"
                name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('form.password')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between">
            <label for="remember" class="inline-flex items-center">
                <input wire:model="form.remember" id="remember" type="checkbox"
                    class="rounded border-zinc-300 text-emerald-600 shadow-sm focus:ring-emerald-500" name="remember">
                <span class="ms-2 text-sm text-zinc-600">{{ __('Remember me') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm text-zinc-600 hover:text-zinc-900 underline" href="{{ route('password.request') }}"
                    wire:navigate>
                    {{ __('Lupa password?') }}
                </a>
            @endif
        </div>

        <x-primary-button class="w-full justify-center py-3">
            {{ __('Masuk') }}
        </x-primary-button>
    </form>

    <div class="relative my-6">
        <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-zinc-200"></div>
        </div>
        <div class="relative flex justify-center text-xs">
            <span class="bg-white px-3 text-zinc-400">atau</span>
        </div>
    </div>

    <a href="{{ route('google.redirect') }}"
        class="w-full flex items-center justify-center gap-2.5 py-3 border border-zinc-200 rounded-lg text-sm font-medium text-zinc-700 hover:bg-zinc-50 transition-colors">
        <svg width="18" height="18" viewBox="0 0 24 24">
            <path fill="#4285F4"
                d="M23.52 12.27c0-.85-.08-1.67-.22-2.45H12v4.63h6.47c-.28 1.5-1.13 2.77-2.4 3.63v3h3.89c2.28-2.1 3.56-5.2 3.56-8.81z" />
            <path fill="#34A853"
                d="M12 24c3.24 0 5.96-1.07 7.95-2.92l-3.89-3c-1.08.72-2.45 1.15-4.06 1.15-3.13 0-5.78-2.11-6.73-4.95H1.26v3.1C3.24 21.3 7.28 24 12 24z" />
            <path fill="#FBBC05"
                d="M5.27 14.28A7.2 7.2 0 0 1 4.88 12c0-.79.14-1.56.39-2.28V6.62H1.26A11.98 11.98 0 0 0 0 12c0 1.94.46 3.77 1.26 5.38l4.01-3.1z" />
            <path fill="#EA4335"
                d="M12 4.77c1.76 0 3.35.61 4.6 1.8l3.44-3.44C17.96 1.19 15.24 0 12 0 7.28 0 3.24 2.7 1.26 6.62l4.01 3.1C6.22 6.88 8.87 4.77 12 4.77z" />
        </svg>
        Masuk dengan Google
    </a>

    @if (Route::has('register'))
        <p class="text-center text-sm text-zinc-500 mt-6">
            Belum punya akun?
            <a href="{{ route('register') }}" wire:navigate class="text-emerald-600 font-medium hover:underline">Daftar
                sekarang</a>
        </p>
    @endif
</div>
