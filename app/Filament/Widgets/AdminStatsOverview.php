<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Models\Sekolah;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** Angka ringkas di bagian atas dashboard admin (doc/LANGGANAN.md §10). */
class AdminStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $baru7 = Sekolah::where('created_at', '>=', now()->subDays(7))->count();
        $baru30 = Sekolah::where('created_at', '>=', now()->subDays(30))->count();

        // "aktif" = punya minimal satu user yang login bulan ini
        $aktifBulanIni = User::where('role', 'sekolah')
            ->whereNotNull('sekolah_id')
            ->where('last_login_at', '>=', now()->startOfMonth())
            ->distinct()
            ->count('sekolah_id');

        $lunas = fn() => Invoice::where('status', Invoice::LUNAS);
        $pendapatanBulan = (int) $lunas()->whereBetween('diverifikasi_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('total_bayar');
        $pendapatanTahun = (int) $lunas()->whereBetween('diverifikasi_at', [now()->startOfYear(), now()->endOfYear()])->sum('total_bayar');

        $menunggu = Invoice::where('status', Invoice::MENUNGGU_VERIFIKASI)->count();
        $belumOnboarding = User::where('role', 'sekolah')->whereNull('sekolah_id')->count();

        return [
            Stat::make('Total Sekolah', Sekolah::count())
                ->description("+{$baru7} dalam 7 hari terakhir")
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            Stat::make('Pendaftar Baru (30 hari)', $baru30),
            Stat::make('Sekolah Aktif Bulan Ini', $aktifBulanIni)->description('Minimal 1 user login bulan ini'),
            Stat::make('Pendapatan Bulan Ini', $this->rupiah($pendapatanBulan))->description('Tahun ini: ' . $this->rupiah($pendapatanTahun)),
            Stat::make('Menunggu Verifikasi', $menunggu)
                ->description($menunggu > 0 ? 'Perlu tindakan admin' : 'Semua sudah diproses')
                ->color($menunggu > 0 ? 'warning' : 'success'),
            Stat::make('Belum Melengkapi Profil', $belumOnboarding)->description('Sudah daftar, belum isi data sekolah'),
        ];
    }

    protected function rupiah(int $nilai): string
    {
        return 'Rp ' . number_format($nilai, 0, ',', '.');
    }
}
