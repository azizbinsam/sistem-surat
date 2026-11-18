<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\InvoiceResource;
use App\Models\Invoice;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/** Antrean kerja utama admin: invoice yang sudah ada bukti transfernya. Aksinya sama persis dengan menu Invoice. */
class InvoiceMenungguVerifikasiWidget extends TableWidget
{
    protected static ?string $heading = 'Invoice Menunggu Verifikasi';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(Invoice::query()->where('status', Invoice::MENUNGGU_VERIFIKASI)->with('sekolah', 'items.tahunAnggaran')->orderBy('dibayar_at'))
            ->columns([
                Tables\Columns\TextColumn::make('nomor'),
                Tables\Columns\TextColumn::make('sekolah.nama_sekolah')->label('Sekolah'),
                InvoiceResource::kolomTriwulan(),
                Tables\Columns\TextColumn::make('total_bayar')->label('Total Bayar')->money('IDR'),
                Tables\Columns\TextColumn::make('dibayar_at')->label('Bukti Diunggah')->since(),
            ])
            ->actions([
                InvoiceResource::aksiBukti(),
                InvoiceResource::aksiSetujui(),
                InvoiceResource::aksiTolak(),
            ])
            ->emptyStateHeading('Tidak ada invoice yang menunggu verifikasi')
            ->paginated([5, 10]);
    }
}
