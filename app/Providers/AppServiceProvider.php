<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Ganti view pagination default Laravel (ada class dark: bawaan yang bikin
        // kelihatan gelap padahal tema app ini terang) ke view custom kita sendiri —
        // berlaku otomatis buat SEMUA ->links() di seluruh app (Fase 17).
        Paginator::defaultView('pagination.custom');
        Paginator::defaultSimpleView('pagination.custom');

        // Laravel 11 nggak lagi otomatis daftar listener ini (beda dari versi lama
        // yang punya EventServiceProvider bawaan) — jadi walau User sudah
        // implements MustVerifyEmail, TANPA baris ini email verifikasi nggak
        // akan pernah terkirim saat registrasi. Ini yang bikin fitur ini
        // sebelumnya sama sekali nggak jalan.
        Event::listen(Registered::class, SendEmailVerificationNotification::class);
    }
}