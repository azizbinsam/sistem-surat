<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RekeningDonasiResource\Pages;
use App\Models\RekeningDonasi;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RekeningDonasiResource extends Resource
{
    protected static ?string $model = RekeningDonasi::class;

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?string $navigationLabel = 'Rekening Donasi';

    protected static ?string $navigationGroup = 'Pengaturan Platform';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('nama_bank')
                ->required()
                ->maxLength(255)
                ->placeholder('BCA, Mandiri, BRI, dst'),

            Forms\Components\TextInput::make('nomor_rekening')
                ->required()
                ->maxLength(50),

            Forms\Components\TextInput::make('atas_nama')
                ->required()
                ->maxLength(255),

            Forms\Components\FileUpload::make('foto')
                ->label('Foto Buku Rekening / QRIS')
                ->image()
                ->directory('rekening-donasi')
                ->disk('public'),

            Forms\Components\TextInput::make('urutan')
                ->numeric()
                ->default(0)
                ->helperText('Angka lebih kecil tampil lebih dulu.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('urutan')
            ->columns([
                Tables\Columns\ImageColumn::make('foto')->disk('public')->circular(),
                Tables\Columns\TextColumn::make('nama_bank')->searchable(),
                Tables\Columns\TextColumn::make('nomor_rekening'),
                Tables\Columns\TextColumn::make('atas_nama'),
                Tables\Columns\TextColumn::make('urutan')->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRekeningDonasis::route('/'),
            'create' => Pages\CreateRekeningDonasi::route('/create'),
            'edit' => Pages\EditRekeningDonasi::route('/{record}/edit'),
        ];
    }
}
