<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\SekolahResource;
use App\Models\Sekolah;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/** Sekolah yang menumpuk transaksi draft: paling mungkin butuh (dan mau) membeli paket. */
class CalonPembeliWidget extends TableWidget
{
    protected static ?string $heading = 'Calon Pembeli — Draft Belum Di-generate';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Sekolah::query()
                    ->withCount([
                        'transaksi as draft_count' => fn($q) => $q->where('status', 'draft'),
                        'hakTriwulan as hak_aktif_count' => fn($q) => $q->aktif(),
                    ])
                    ->withMax('users', 'last_login_at')
                    ->whereHas('transaksi', fn($q) => $q->where('status', 'draft'))
                    ->orderByDesc('draft_count')
            )
            ->columns([
                Tables\Columns\TextColumn::make('nama_sekolah')->label('Sekolah'),
                Tables\Columns\TextColumn::make('draft_count')->label('Draft'),
                Tables\Columns\TextColumn::make('hak_aktif_count')->label('Triwulan aktif'),
                Tables\Columns\TextColumn::make('generate_percobaan_terpakai')
                    ->label('Kuota percobaan')
                    ->formatStateUsing(fn($state) => (int) $state . '/' . (int) config('langganan.kuota_percobaan')),
                Tables\Columns\TextColumn::make('users_max_last_login_at')->label('Terakhir aktif')->dateTime('d-m-Y H:i')->placeholder('Belum pernah'),
            ])
            ->actions([
                Tables\Actions\Action::make('lihat')
                    ->label('Lihat')
                    ->icon('heroicon-o-eye')
                    ->url(fn(Sekolah $record) => SekolahResource::getUrl('view', ['record' => $record])),
            ])
            ->emptyStateHeading('Belum ada sekolah dengan transaksi draft')
            ->paginated([5, 10]);
    }
}
