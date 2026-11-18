<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/** User yang sudah mendaftar tetapi belum mengisi data sekolah — kandidat untuk di-follow up. */
class BelumOnboardingWidget extends TableWidget
{
    protected static ?string $heading = 'Belum Menyelesaikan Onboarding';

    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(User::query()->where('role', 'sekolah')->whereNull('sekolah_id')->latest())
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nama'),
                Tables\Columns\TextColumn::make('email'),
                Tables\Columns\IconColumn::make('email_verified_at')->label('Email terverifikasi')->boolean(),
                Tables\Columns\TextColumn::make('created_at')->label('Mendaftar')->since(),
            ])
            ->emptyStateHeading('Semua user sudah melengkapi profil sekolah')
            ->paginated([5, 10]);
    }
}
