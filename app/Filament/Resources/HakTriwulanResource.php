<?php

namespace App\Filament\Resources;

use App\Exceptions\LanggananException;
use App\Filament\Resources\HakTriwulanResource\Pages;
use App\Models\HakTriwulan;
use App\Models\Sekolah;
use App\Models\TahunAnggaran;
use App\Services\AksesTriwulanService;
use App\Support\Triwulan;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Daftar hak triwulan semua sekolah + pemberian manual (promo/komplimen) + pencabutan.
 * Hak berbayar dibuat otomatis saat invoice disetujui, jadi tidak ada form "tambah" biasa di sini.
 */
class HakTriwulanResource extends Resource
{
    protected static ?string $model = HakTriwulan::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationLabel = 'Hak Triwulan';

    protected static ?string $navigationGroup = 'Keuangan';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Hak Triwulan';

    protected static ?string $pluralModelLabel = 'Hak Triwulan';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('sekolah', 'tahunAnggaran');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('sekolah.nama_sekolah')->label('Sekolah')->searchable(),
                Tables\Columns\TextColumn::make('tahunAnggaran.tahun')->label('Tahun'),
                Tables\Columns\TextColumn::make('triwulan')->formatStateUsing(fn($state) => 'TW' . $state)->sortable(),
                Tables\Columns\TextColumn::make('sumber')
                    ->badge()
                    ->color(fn(string $state) => match ($state) {
                        'berbayar' => 'success',
                        'gratis' => 'info',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state) => $state === 'aktif' ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('berlaku_sampai')->label('Berlaku sampai')->date('d-m-Y')->placeholder('Tanpa batas'),
                Tables\Columns\TextColumn::make('catatan')->limit(40)->placeholder('-')->toggleable(),
                Tables\Columns\TextColumn::make('created_at')->label('Dibuat')->dateTime('d-m-Y')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(['aktif' => 'Aktif', 'dicabut' => 'Dicabut']),
                Tables\Filters\SelectFilter::make('sumber')->options(['berbayar' => 'Berbayar', 'gratis' => 'Gratis', 'manual' => 'Diberikan admin']),
                Tables\Filters\SelectFilter::make('triwulan')->options([1 => 'TW1', 2 => 'TW2', 3 => 'TW3', 4 => 'TW4']),
            ])
            ->headerActions([
                Tables\Actions\Action::make('beri')
                    ->label('Beri Hak')
                    ->icon('heroicon-o-gift')
                    ->modalHeading('Beri hak triwulan tanpa pembayaran')
                    ->form([
                        Forms\Components\Select::make('sekolah_id')
                            ->label('Sekolah')
                            ->options(fn() => Sekolah::orderBy('nama_sekolah')->pluck('nama_sekolah', 'id'))
                            ->searchable()
                            ->live()
                            ->required(),
                        Forms\Components\Select::make('tahun_anggaran_id')
                            ->label('Tahun Anggaran')
                            ->options(fn(Forms\Get $get) => $get('sekolah_id')
                                ? TahunAnggaran::where('sekolah_id', $get('sekolah_id'))->orderByDesc('tahun')->pluck('tahun', 'id')
                                : [])
                            ->required(),
                        Forms\Components\Select::make('triwulan')
                            ->options(Triwulan::NAMA)
                            ->required(),
                        Forms\Components\Select::make('sumber')
                            ->options(['manual' => 'Diberikan admin (tanpa batas waktu)', 'gratis' => 'Gratis (promo, biasanya berbatas waktu)'])
                            ->default('manual')
                            ->required(),
                        Forms\Components\DatePicker::make('berlaku_sampai')
                            ->label('Berlaku sampai (opsional)')
                            ->helperText('Kosongkan untuk tanpa batas waktu.'),
                        Forms\Components\TextInput::make('catatan')->maxLength(255)->placeholder('mis. sekolah percontohan'),
                    ])
                    ->action(function (array $data) {
                        try {
                            $sekolah = Sekolah::findOrFail($data['sekolah_id']);
                            $tahunAnggaran = TahunAnggaran::findOrFail($data['tahun_anggaran_id']);

                            app(AksesTriwulanService::class)->berikanHak(
                                $sekolah,
                                $tahunAnggaran,
                                (int) $data['triwulan'],
                                $data['sumber'],
                                filled($data['berlaku_sampai'] ?? null) ? \Illuminate\Support\Carbon::parse($data['berlaku_sampai']) : null,
                                $data['catatan'] ?? null,
                            );
                        } catch (LanggananException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                            return;
                        }

                        Notification::make()->title('Hak triwulan diberikan.')->success()->send();
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('cabut')
                    ->label('Cabut')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('Sekolah tidak bisa generate surat baru di triwulan ini. Surat yang sudah di-generate tidak terpengaruh.')
                    ->visible(fn(HakTriwulan $record) => $record->status === 'aktif')
                    ->action(function (HakTriwulan $record) {
                        app(AksesTriwulanService::class)->cabutHak($record);
                        Notification::make()->title('Hak dicabut.')->success()->send();
                    }),
                Tables\Actions\Action::make('aktifkan')
                    ->label('Aktifkan kembali')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn(HakTriwulan $record) => $record->status === 'dicabut')
                    ->action(function (HakTriwulan $record) {
                        app(AksesTriwulanService::class)->aktifkanKembaliHak($record);
                        Notification::make()->title('Hak diaktifkan kembali.')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHakTriwulans::route('/'),
        ];
    }
}
