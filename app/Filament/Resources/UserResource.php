<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use App\Support\EksporCsv;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'User';

    protected static ?string $navigationGroup = 'Sekolah & User';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('email')
                ->email()
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),

            Forms\Components\Select::make('role')
                ->options([
                    'admin' => 'Admin',
                    'sekolah' => 'Sekolah',
                ])
                ->required()
                ->live(),

            Forms\Components\Select::make('sekolah_id')
                ->relationship('sekolah', 'nama_sekolah')
                ->searchable()
                ->visible(fn(Forms\Get $get) => $get('role') === 'sekolah'),

            Forms\Components\TextInput::make('password')
                ->password()
                ->dehydrateStateUsing(fn($state) => \Illuminate\Support\Facades\Hash::make($state))
                ->dehydrated(fn($state) => filled($state))
                ->required(fn(string $context) => $context === 'create')
                ->helperText('Kosongkan kalau tidak ingin mengubah password.'),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('sekolah');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nama')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->searchable()->sortable(),
                Tables\Columns\BadgeColumn::make('role')
                    ->colors([
                        'zinc' => 'sekolah',
                        'warning' => 'admin',
                    ]),
                Tables\Columns\TextColumn::make('sekolah.nama_sekolah')->label('Sekolah')->placeholder('-')->searchable(),
                Tables\Columns\TextColumn::make('sekolah.npsn')->label('NPSN')->placeholder('-')->toggleable(),
                Tables\Columns\TextColumn::make('daftar_via')
                    ->label('Daftar via')
                    ->badge()
                    ->state(fn(User $record) => $record->google_id ? 'Google' : 'Email'),
                Tables\Columns\IconColumn::make('email_verified_at')->label('Email terverifikasi')->boolean(),
                Tables\Columns\TextColumn::make('last_login_at')->label('Terakhir login')->dateTime('d-m-Y H:i')->sortable()->placeholder('Belum pernah'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d-m-Y')
                    ->sortable()
                    ->label('Terdaftar'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'admin' => 'Admin',
                        'sekolah' => 'Sekolah',
                    ]),
                Tables\Filters\TernaryFilter::make('email_verified_at')->label('Email terverifikasi')->nullable(),
                Tables\Filters\Filter::make('belum_onboarding')
                    ->label('Belum melengkapi profil sekolah')
                    ->query(fn(Builder $query) => $query->where('role', 'sekolah')->whereNull('sekolah_id')),
                Tables\Filters\Filter::make('belum_pernah_login')
                    ->label('Belum pernah login')
                    ->query(fn(Builder $query) => $query->whereNull('last_login_at')),
                Tables\Filters\Filter::make('tidak_aktif_30_hari')
                    ->label('Tidak aktif 30 hari')
                    ->query(fn(Builder $query) => $query->where(fn($w) => $w->whereNull('last_login_at')->orWhere('last_login_at', '<', now()->subDays(30)))),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('promote')
                    ->label('Promote ke Admin')
                    ->icon('heroicon-o-arrow-up-circle')
                    ->color('warning')
                    ->visible(fn($record) => $record->role === 'sekolah')
                    ->requiresConfirmation()
                    ->action(fn($record) => $record->update(['role' => 'admin'])),
                Tables\Actions\DeleteAction::make(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('ekspor')
                    ->label('Ekspor CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(function ($livewire) {
                        $baris = $livewire->getFilteredSortedTableQuery()->get()->map(fn(User $u) => [
                            $u->name,
                            $u->email,
                            $u->role,
                            $u->sekolah?->nama_sekolah,
                            $u->sekolah?->npsn,
                            $u->google_id ? 'Google' : 'Email',
                            $u->email_verified_at ? 'Ya' : 'Belum',
                            $u->last_login_at?->format('Y-m-d H:i'),
                            $u->created_at?->format('Y-m-d'),
                        ]);

                        return EksporCsv::unduh('user-' . now()->format('Ymd-His') . '.csv', [
                            'Nama', 'Email', 'Role', 'Sekolah', 'NPSN', 'Daftar via', 'Email terverifikasi', 'Terakhir login', 'Terdaftar',
                        ], $baris);
                    }),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
