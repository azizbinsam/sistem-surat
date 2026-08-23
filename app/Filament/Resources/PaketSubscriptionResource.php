<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaketSubscriptionResource\Pages;
use App\Filament\Resources\PaketSubscriptionResource\RelationManagers;
use App\Models\PaketSubscription;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaketSubscriptionResource extends Resource
{
    protected static ?string $model = PaketSubscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('nama_paket')
                ->required()
                ->maxLength(255)
                ->placeholder('Bulanan, Tahunan, dst'),

            Forms\Components\TextInput::make('harga')
                ->required()
                ->numeric()
                ->prefix('Rp'),

            Forms\Components\TextInput::make('durasi_hari')
                ->required()
                ->numeric()
                ->suffix('hari'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_paket')->searchable(),
                Tables\Columns\TextColumn::make('harga')->money('IDR'),
                Tables\Columns\TextColumn::make('durasi_hari')->suffix(' hari'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaketSubscriptions::route('/'),
            'create' => Pages\CreatePaketSubscription::route('/create'),
            'edit' => Pages\EditPaketSubscription::route('/{record}/edit'),
        ];
    }
}
