<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Filament\Widgets\ChartWidget;

class PendapatanChart extends ChartWidget
{
    protected static ?string $heading = 'Pendapatan per Bulan (Rp)';

    protected static ?int $sort = 4;

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $bulan = collect(range(11, 0))->map(fn($i) => now()->startOfMonth()->subMonths($i));

        $perBulan = Invoice::where('status', Invoice::LUNAS)
            ->where('diverifikasi_at', '>=', $bulan->first())
            ->get(['diverifikasi_at', 'total_bayar'])
            ->groupBy(fn($i) => $i->diverifikasi_at->format('Y-m'))
            ->map(fn($grup) => (int) $grup->sum('total_bayar'));

        return [
            'datasets' => [[
                'label' => 'Pendapatan',
                'data' => $bulan->map(fn($b) => $perBulan->get($b->format('Y-m'), 0))->all(),
            ]],
            'labels' => $bulan->map(fn($b) => $b->format('M Y'))->all(),
        ];
    }
}
