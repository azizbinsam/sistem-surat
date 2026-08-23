<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionResource\Pages;
use App\Filament\Resources\SubscriptionResource\RelationManagers;
use App\Models\Subscription;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('sekolah_id')
                ->relationship('sekolah', 'nama_sekolah')
                ->searchable()
                ->required(),

            Forms\Components\Select::make('paket_id')
                ->relationship('paket', 'nama_paket')
                ->searchable()
                ->required(),

            Forms\Components\Select::make('status')
                ->options([
                    'aktif' => 'Aktif',
                    'hold' => 'Hold',
                    'expired' => 'Expired',
                ])
                ->required(),

            Forms\Components\DatePicker::make('tanggal_mulai')->required(),
            Forms\Components\DatePicker::make('tanggal_berakhir')->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sekolah.nama_sekolah')->searchable(),
                Tables\Columns\TextColumn::make('paket.nama_paket'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'aktif',
                        'warning' => 'hold',
                        'danger' => 'expired',
                    ]),
                Tables\Columns\TextColumn::make('tanggal_mulai')->date('d-m-Y'),
                Tables\Columns\TextColumn::make('tanggal_berakhir')->date('d-m-Y'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'aktif' => 'Aktif',
                        'hold' => 'Hold',
                        'expired' => 'Expired',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('perpanjang')
                    ->label('Perpanjang')
                    ->icon('heroicon-o-calendar')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $mulaiDari = $record->tanggal_berakhir->isFuture() ? $record->tanggal_berakhir : now();
                        $record->update([
                            'status' => 'aktif',
                            'tanggal_berakhir' => $mulaiDari->copy()->addDays($record->paket->durasi_hari),
                            'dibuat_manual_oleh' => auth()->id(),
                        ]);
                    }),

                Tables\Actions\Action::make('hold')
                    ->label('Hold')
                    ->icon('heroicon-o-pause-circle')
                    ->color('warning')
                    ->visible(fn($record) => $record->status === 'aktif')
                    ->requiresConfirmation()
                    ->action(fn($record) => $record->update(['status' => 'hold'])),

                Tables\Actions\Action::make('aktifkan')
                    ->label('Aktifkan Lagi')
                    ->icon('heroicon-o-play-circle')
                    ->color('success')
                    ->visible(fn($record) => $record->status === 'hold')
                    ->action(fn($record) => $record->update(['status' => 'aktif'])),

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
            'index' => Pages\ListSubscriptions::route('/'),
            'create' => Pages\CreateSubscription::route('/create'),
            'edit' => Pages\EditSubscription::route('/{record}/edit'),
        ];
    }
}
