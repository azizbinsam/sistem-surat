<?php

namespace App\Filament\Pages;

use App\Models\Sekolah;
use App\Models\TahunAnggaran;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class KelolaTahunAnggaran extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Tahun Anggaran';

    protected static ?string $navigationGroup = 'Pengaturan Platform';

    protected static string $view = 'filament.pages.kelola-tahun-anggaran';

    /**
     * Ringkasan per tahun (lintas semua sekolah) — ini yang ditampilin sebagai daftar
     * di halaman, biar admin bisa lihat riwayat tahun anggaran yang pernah dibuka
     * dan status "aktif untuk berapa sekolah" per tahunnya.
     */
    public function getRingkasanTahun()
    {
        return TahunAnggaran::selectRaw('tahun')
            ->selectRaw("SUM(CASE WHEN status = 'aktif' THEN 1 ELSE 0 END) as jumlah_aktif")
            ->selectRaw("SUM(CASE WHEN status = 'hold' THEN 1 ELSE 0 END) as jumlah_hold")
            ->selectRaw('COUNT(*) as jumlah_sekolah')
            ->groupBy('tahun')
            ->orderByDesc('tahun')
            ->get();
    }

    public function getJumlahSekolah(): int
    {
        return Sekolah::count();
    }

    public function getHeaderActions(): array
    {
        $tahunBaru = (TahunAnggaran::max('tahun') ?? (now()->year - 1)) + 1;

        return [
            Action::make('bukaTahunAnggaranBaru')
                ->label("Buka Tahun Anggaran {$tahunBaru}")
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading("Buka Tahun Anggaran {$tahunBaru} untuk SEMUA sekolah?")
                ->modalDescription(
                    "Setiap sekolah ({$this->getJumlahSekolah()} sekolah) akan otomatis dapat 1 baris tahun anggaran baru ({$tahunBaru}) dengan status HOLD — belum langsung aktif, jadi sekolah TETAP jalan di tahun anggaran yang sekarang sampai kamu aktifkan manual dari daftar di bawah. Aman diklik kapan aja, nggak akan mengganggu operasional sekolah."
                )
                ->modalSubmitActionLabel('Ya, Buka Sekarang')
                ->action(fn() => $this->bukaTahunAnggaranBaru($tahunBaru)),
        ];
    }

    public function bukaTahunAnggaranBaru(int $tahunBaru): void
    {
        $jumlahDibuat = 0;

        DB::transaction(function () use ($tahunBaru, &$jumlahDibuat) {
            Sekolah::chunk(50, function ($sekolahList) use ($tahunBaru, &$jumlahDibuat) {
                foreach ($sekolahList as $sekolah) {
                    if ($sekolah->tahunAnggaran()->where('tahun', $tahunBaru)->exists()) {
                        continue;
                    }

                    // Status HOLD, sengaja TIDAK menyentuh baris "aktif" yang sudah ada —
                    // sekolah tetap jalan normal di tahun anggaran lama sampai admin
                    // aktifkan tahun baru ini secara eksplisit lewat tombol di bawah.
                    $sekolah->tahunAnggaran()->create([
                        'tahun' => $tahunBaru,
                        'nomor_urut_terakhir' => 0,
                        'status' => 'hold',
                    ]);
                    $jumlahDibuat++;
                }
            });
        });

        Notification::make()
            ->title("Tahun Anggaran {$tahunBaru} dibuka (status hold) untuk {$jumlahDibuat} sekolah")
            ->success()
            ->send();
    }

    /**
     * Satu-satunya cara tahun anggaran jadi "aktif" (dipakai default sekolah) — sekaligus
     * ini yang jadi tombol rollback: kalau nggak sengaja aktifin tahun yang salah, admin
     * tinggal klik "Aktifkan" lagi di tahun yang seharusnya, dan status balik otomatis.
     */
    public function aktifkanTahun(int $tahun): void
    {
        $jumlahDiubah = 0;

        DB::transaction(function () use ($tahun, &$jumlahDiubah) {
            Sekolah::chunk(50, function ($sekolahList) use ($tahun, &$jumlahDiubah) {
                foreach ($sekolahList as $sekolah) {
                    $target = $sekolah->tahunAnggaran()->where('tahun', $tahun)->first();

                    if (!$target || $target->status === 'aktif') {
                        continue;
                    }

                    // Maksimal 1 baris "aktif" per sekolah — yang lama otomatis turun jadi hold.
                    $sekolah->tahunAnggaran()->where('status', 'aktif')->update(['status' => 'hold']);
                    $target->update(['status' => 'aktif']);
                    $jumlahDiubah++;
                }
            });
        });

        Notification::make()
            ->title("Tahun Anggaran {$tahun} sekarang aktif untuk {$jumlahDiubah} sekolah")
            ->success()
            ->send();
    }
}
