<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
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
    }
}