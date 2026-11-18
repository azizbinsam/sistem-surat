<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\KwitansiPdfGenerator;

class KwitansiController extends Controller
{
    public function __invoke(Invoice $invoice, KwitansiPdfGenerator $generator)
    {
        // Sekolah hanya boleh mengunduh kwitansi miliknya, dan hanya untuk invoice yang sudah lunas.
        // 404 (bukan 403) supaya keberadaan invoice sekolah lain tidak bocor.
        abort_unless($invoice->sekolah_id === auth()->user()->sekolah_id, 404);
        abort_unless($invoice->status === Invoice::LUNAS, 404);

        return response($generator->render($invoice), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $generator->namaFile($invoice) . '"',
        ]);
    }
}
