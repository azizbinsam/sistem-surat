<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaketLanggananResource\Pages;
use App\Models\PaketLangganan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Katalog harga langganan (doc/LANGGANAN.md §3). Hanya HARGA yang bisa diubah di sini —
 * dua paket (Triwulan & Tahunan) adalah bagian dari aturan sistem, jadi tidak ada tombol tambah/hapus.
 */
class PaketLanggananResource extends Resource
{
    protected static ?string $model = PaketLangganan::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Paket & Harga';

    protected static ?string $navigationGroup = 'Keuangan';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Paket';

    protected static ?string $pluralModelLabel = 'Paket & Harga';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('nama')
                ->disabled()
                ->dehydrated(false),

            Forms\Components\TextInput::make('harga')
                ->required()
                ->numeric()
                ->minValue(1)
                ->prefix('Rp')
                ->helperText('Berlaku untuk invoice yang dibuat SETELAH diubah. Invoice yang sudah ada tidak berubah.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama'),
                Tables\Columns\TextColumn::make('jumlah_triwulan')->label('Isi paket')->suffix(' triwulan'),
                Tables\Columns\TextColumn::make('harga')->money('IDR'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaketLangganans::route('/'),
            'edit' => Pages\EditPaketLangganan::route('/{record}/edit'),
        ];
    }
}
