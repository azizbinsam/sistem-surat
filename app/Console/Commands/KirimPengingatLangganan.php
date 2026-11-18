<?php

namespace App\Console\Commands;

use App\Services\PengingatLanggananService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class KirimPengingatLangganan extends Command
{
    protected $signature = 'langganan:kirim-pengingat {--tanggal= : Anggap hari ini = tanggal ini (YYYY-MM-DD), berguna untuk uji coba}';

    protected $description = 'Kirim pengingat langganan: invoice hampir kedaluwarsa, invoice menunggu verifikasi (admin), dan draft belum bisa di-generate';

    public function handle(PengingatLanggananService $service): int
    {
        $tanggal = $this->option('tanggal');

        if ($tanggal !== null && !Carbon::hasFormat($tanggal, 'Y-m-d')) {
            $this->error('Format --tanggal harus YYYY-MM-DD.');

            return self::FAILURE;
        }

        $sekarang = $tanggal ? Carbon::createFromFormat('Y-m-d', $tanggal)->setTime(8, 0) : now();
        $hariIni = $sekarang->copy()->startOfDay();

        $this->info('Invoice hampir kedaluwarsa  : ' . $service->invoiceHampirKedaluwarsa($hariIni) . ' email');
        $this->info('Invoice menunggu verifikasi : ' . $service->invoiceMenungguVerifikasi($sekarang) . ' email ke admin');
        $this->info('Draft belum bisa generate   : ' . $service->draftBelumBisaGenerate($hariIni) . ' email');

        return self::SUCCESS;
    }
}
