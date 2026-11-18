<?php

namespace App\Filament\Resources;

use App\Exceptions\LanggananException;
use App\Filament\Resources\InvoiceResource\Pages;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

/**
 * Daftar invoice + aksi Setujui / Tolak (doc/LANGGANAN.md §5, §10).
 * Tidak ada buat/ubah manual: invoice hanya dibuat sekolah lewat halaman Langganan.
 */
class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationLabel = 'Invoice';

    protected static ?string $navigationGroup = 'Keuangan';

    public static function canCreate(): bool
    {
        return false;
    }

    /** Angka di menu = jumlah invoice yang menunggu diverifikasi admin. */
    public static function getNavigationBadge(): ?string
    {
        $jumlah = Invoice::where('status', Invoice::MENUNGGU_VERIFIKASI)->count();

        return $jumlah > 0 ? (string) $jumlah : null;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('sekolah', 'items.tahunAnggaran');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('nomor')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('sekolah.nama_sekolah')->label('Sekolah')->searchable(),
                Tables\Columns\TextColumn::make('triwulan')
                    ->label('Triwulan')
                    ->state(fn(Invoice $record) => $record->items
                        ->map(fn($i) => "TW{$i->triwulan} {$i->tahunAnggaran->tahun}")
                        ->implode(', ')),
                Tables\Columns\TextColumn::make('total_bayar')->label('Total Bayar')->money('IDR')->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn(Invoice $record) => $record->labelStatus())
                    ->color(fn(string $state) => match ($state) {
                        Invoice::LUNAS => 'success',
                        Invoice::MENUNGGU_VERIFIKASI => 'info',
                        Invoice::MENUNGGU_PEMBAYARAN => 'warning',
                        Invoice::DITOLAK => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('dibayar_at')->label('Bukti Diunggah')->dateTime('d-m-Y H:i')->placeholder('-'),
                Tables\Columns\TextColumn::make('jatuh_tempo_at')->label('Jatuh Tempo')->dateTime('d-m-Y H:i')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')->label('Dibuat')->dateTime('d-m-Y H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    Invoice::MENUNGGU_VERIFIKASI => 'Menunggu verifikasi',
                    Invoice::MENUNGGU_PEMBAYARAN => 'Menunggu pembayaran',
                    Invoice::LUNAS => 'Lunas',
                    Invoice::DITOLAK => 'Ditolak',
                    Invoice::KEDALUWARSA => 'Kedaluwarsa',
                    Invoice::DIBATALKAN => 'Dibatalkan',
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('bukti')
                    ->label('Lihat Bukti')
                    ->icon('heroicon-o-photo')
                    ->color('gray')
                    ->url(fn(Invoice $record) => $record->bukti_transfer ? Storage::disk('public')->url($record->bukti_transfer) : null)
                    ->openUrlInNewTab()
                    ->visible(fn(Invoice $record) => filled($record->bukti_transfer)),

                Tables\Actions\Action::make('setujui')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Setujui pembayaran ini?')
                    ->modalDescription(fn(Invoice $record) => 'Pastikan Rp ' . number_format($record->total_bayar, 0, ',', '.') . ' sudah masuk ke rekening. Triwulan di invoice ini akan langsung aktif.')
                    ->visible(fn(Invoice $record) => $record->bisaDisetujui())
                    ->action(function (Invoice $record) {
                        try {
                            app(InvoiceService::class)->setujui($record, auth()->user());
                        } catch (LanggananException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                            return;
                        }

                        Notification::make()->title('Pembayaran disetujui, triwulan sudah aktif.')->success()->send();
                    }),

                Tables\Actions\Action::make('tolak')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Forms\Components\Textarea::make('alasan')
                            ->label('Alasan penolakan')
                            ->helperText('Ditampilkan ke sekolah supaya mereka tahu apa yang perlu diperbaiki.')
                            ->required()
                            ->maxLength(500),
                    ])
                    ->visible(fn(Invoice $record) => $record->bisaDitolak())
                    ->action(function (Invoice $record, array $data) {
                        try {
                            app(InvoiceService::class)->tolak($record, auth()->user(), $data['alasan']);
                        } catch (LanggananException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                            return;
                        }

                        Notification::make()->title('Invoice ditolak.')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
        ];
    }
}
