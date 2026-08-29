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

    public function getTahunTerbaru(): ?int
    {
        return TahunAnggaran::max('tahun');
    }

    public function getJumlahSekolah(): int
    {
        return Sekolah::count();
    }

    public function getHeaderActions(): array
    {
        $tahunBaru = ($this->getTahunTerbaru() ?? (now()->year - 1)) + 1;

        return [
            Action::make('bukaTahunAnggaranBaru')
                ->label("Buka Tahun Anggaran {$tahunBaru}")
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading("Buka Tahun Anggaran {$tahunBaru} untuk SEMUA sekolah?")
                ->modalDescription(
                    "Setiap sekolah ({$this->getJumlahSekolah()} sekolah) akan otomatis dapat 1 tahun anggaran baru ({$tahunBaru}), mulai dari nol (nomor urut surat reset ke 0, master barang & pegawai kosong). Data tahun anggaran lama TETAP AMAN dan tetap bisa diakses/diedit dengan pindah tahun anggaran. Aksi ini tidak bisa dibatalkan."
                )
                ->modalSubmitActionLabel('Ya, Buka Sekarang')
                ->action(fn() => $this->bukaTahunAnggaranBaru($tahunBaru)),
        ];
    }

    public function bukaTahunAnggaranBaru(int $tahunBaru): void
    {
        DB::transaction(function () use ($tahunBaru) {
            Sekolah::chunk(50, function ($sekolahList) use ($tahunBaru) {
                foreach ($sekolahList as $sekolah) {
                    // Kalau tahun ini SUDAH pernah dibuka buat sekolah tertentu (mis. re-klik
                    // nggak sengaja), skip biar nggak dobel — unique(sekolah_id, tahun) juga
                    // udah jaga di level database, ini cuma proteksi tambahan yang rapi.
                    if ($sekolah->tahunAnggaran()->where('tahun', $tahunBaru)->exists()) {
                        continue;
                    }

                    $sekolah->tahunAnggaran()->where('is_aktif', true)->update(['is_aktif' => false]);

                    $sekolah->tahunAnggaran()->create([
                        'tahun' => $tahunBaru,
                        'nomor_urut_terakhir' => 0,
                        'is_aktif' => true,
                    ]);
                }
            });
        });

        Notification::make()
            ->title("Tahun Anggaran {$tahunBaru} berhasil dibuka untuk semua sekolah")
            ->success()
            ->send();
    }
}