<?php

namespace App\Console\Commands;

use App\Services\InvoiceService;
use Illuminate\Console\Command;

class KedaluwarsakanInvoice extends Command
{
    protected $signature = 'langganan:kedaluwarsakan-invoice';

    protected $description = 'Tandai invoice yang lewat jatuh tempo (tanpa bukti transfer) sebagai kedaluwarsa';

    public function handle(InvoiceService $service): int
    {
        $jumlah = $service->kedaluwarsakan();
        $this->info("{$jumlah} invoice ditandai kedaluwarsa.");

        return self::SUCCESS;
    }
}
