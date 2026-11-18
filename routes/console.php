<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Fase 24 — invoice yang lewat jatuh tempo tanpa bukti ditandai kedaluwarsa tiap hari.
// Butuh cron job di server: * * * * * php /path/artisan schedule:run
Illuminate\Support\Facades\Schedule::command('langganan:kedaluwarsakan-invoice')->dailyAt('00:10');

// Fase 24B — pengingat langganan harian (sekolah: invoice hampir kedaluwarsa & draft belum bisa di-generate; admin: invoice menggantung).
Illuminate\Support\Facades\Schedule::command('langganan:kirim-pengingat')->dailyAt('08:00');
