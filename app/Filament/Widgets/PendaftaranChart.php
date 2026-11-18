<?php

namespace App\Filament\Widgets;

use App\Models\Sekolah;
use Filament\Widgets\ChartWidget;

class PendaftaranChart extends ChartWidget
{
    protected static ?string $heading = 'Pendaftaran Sekolah per Bulan';

    protected static ?int $sort = 3;

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $bulan = collect(range(11, 0))->map(fn($i) => now()->startOfMonth()->subMonths($i));

        // dikelompokkan di PHP (bukan SQL) supaya sama di MySQL maupun SQLite; datanya kecil
        $perBulan = Sekolah::where('created_at', '>=', $bulan->first())
            ->pluck('created_at')
            ->countBy(fn($tanggal) => $tanggal->format('Y-m'));

        return [
            'datasets' => [[
                'label' => 'Sekolah baru',
                'data' => $bulan->map(fn($b) => $perBulan->get($b->format('Y-m'), 0))->all(),
            ]],
            'labels' => $bulan->map(fn($b) => $b->format('M Y'))->all(),
        ];
    }
}
