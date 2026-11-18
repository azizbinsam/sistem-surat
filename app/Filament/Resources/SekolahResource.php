<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SekolahResource\Pages;
use App\Models\Sekolah;
use App\Support\EksporCsv;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Data lengkap sekolah untuk admin: identitas, aktivitas, dan status langganan — supaya Anda bisa
 * memantau siapa saja yang aktif, siapa yang calon pembeli, dan siapa yang sudah membayar.
 *
 * Catatan teknis: di panel admin tidak ada user sekolah yang login, jadi global scope Tahun Anggaran
 * (BelongsToTahunAnggaran) otomatis tidak aktif — hitungan transaksi di sini mencakup SEMUA tahun anggaran.
 */
class SekolahResource extends Resource
{
    protected static ?string $model = Sekolah::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationLabel = 'Sekolah';

    protected static ?string $navigationGroup = 'Sekolah & User';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Sekolah';

    protected static ?string $pluralModelLabel = 'Sekolah';

    // Sekolah dibuat user lewat onboarding, bukan oleh admin; dan menghapus sekolah berarti menghapus semua datanya.
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount([
                'users',
                'transaksi',
                'transaksi as surat_digenerate_count' => fn($q) => $q->where('status', 'selesai'),
                'transaksi as draft_count' => fn($q) => $q->where('status', 'draft'),
                'hakTriwulan as hak_aktif_count' => fn($q) => $q->aktif(),
            ])
            ->withMax('users', 'last_login_at')
            ->withSum(['invoices as total_pembayaran' => fn($q) => $q->where('status', 'lunas')], 'total_bayar');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('nama_sekolah')->label('Sekolah')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('npsn')->label('NPSN')->searchable(),
                Tables\Columns\TextColumn::make('tempat')->label('Kota/Kabupaten')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('email')->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('kontak_wa')->label('WhatsApp')->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('users_count')->label('User')->sortable(),
                Tables\Columns\TextColumn::make('transaksi_count')->label('Pengeluaran')->sortable(),
                Tables\Columns\TextColumn::make('surat_digenerate_count')->label('Sudah di-generate')->sortable(),
                Tables\Columns\TextColumn::make('draft_count')->label('Draft')->sortable(),
                Tables\Columns\TextColumn::make('hak_aktif_count')->label('Triwulan aktif')->sortable(),
                Tables\Columns\TextColumn::make('generate_percobaan_terpakai')
                    ->label('Kuota percobaan')
                    ->formatStateUsing(fn($state) => (int) $state . '/' . (int) config('langganan.kuota_percobaan'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_pembayaran')->label('Total bayar')->money('IDR')->sortable()->placeholder('Rp0'),
                Tables\Columns\TextColumn::make('users_max_last_login_at')->label('Terakhir aktif')->dateTime('d-m-Y H:i')->sortable()->placeholder('Belum pernah'),
                Tables\Columns\TextColumn::make('created_at')->label('Terdaftar')->dateTime('d-m-Y')->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('belum_pernah_generate')
                    ->label('Belum pernah generate surat')
                    ->query(fn(Builder $query) => $query->whereDoesntHave('transaksi', fn($t) => $t->where('status', 'selesai'))),
                Tables\Filters\Filter::make('draft_menumpuk')
                    ->label('Draft menumpuk (5+)')
                    ->query(fn(Builder $query) => $query->whereHas('transaksi', fn($t) => $t->where('status', 'draft'), '>=', 5)),
                Tables\Filters\Filter::make('punya_hak_aktif')
                    ->label('Punya triwulan aktif')
                    ->query(fn(Builder $query) => $query->whereHas('hakTriwulan', fn($h) => $h->aktif())),
                Tables\Filters\Filter::make('kuota_habis')
                    ->label('Kuota percobaan habis')
                    ->query(fn(Builder $query) => $query->where('generate_percobaan_terpakai', '>=', (int) config('langganan.kuota_percobaan'))),
                Tables\Filters\Filter::make('tidak_aktif_30_hari')
                    ->label('Tidak aktif 30 hari')
                    ->query(fn(Builder $query) => $query->whereDoesntHave('users', fn($u) => $u->where('last_login_at', '>=', now()->subDays(30)))),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('ekspor')
                    ->label('Ekspor CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(function ($livewire) {
                        $baris = $livewire->getFilteredSortedTableQuery()->get()->map(fn(Sekolah $s) => [
                            $s->nama_sekolah,
                            $s->npsn,
                            $s->tempat,
                            $s->email,
                            $s->kontak_wa,
                            $s->users_count,
                            $s->transaksi_count,
                            $s->surat_digenerate_count,
                            $s->draft_count,
                            $s->hak_aktif_count,
                            (int) $s->generate_percobaan_terpakai,
                            (int) $s->total_pembayaran,
                            $s->users_max_last_login_at,
                            $s->created_at?->format('Y-m-d'),
                        ]);

                        return EksporCsv::unduh('sekolah-' . now()->format('Ymd-His') . '.csv', [
                            'Sekolah', 'NPSN', 'Kota/Kabupaten', 'Email', 'WhatsApp', 'Jumlah User', 'Pengeluaran',
                            'Sudah di-generate', 'Draft', 'Triwulan aktif', 'Kuota percobaan terpakai', 'Total bayar (Rp)',
                            'Terakhir aktif', 'Terdaftar',
                        ], $baris);
                    }),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Identitas')->columns(3)->schema([
                Infolists\Components\TextEntry::make('nama_sekolah')->label('Nama sekolah'),
                Infolists\Components\TextEntry::make('npsn')->label('NPSN')->placeholder('-'),
                Infolists\Components\TextEntry::make('kode_sekolah')->label('Kode sekolah')->placeholder('-'),
                Infolists\Components\TextEntry::make('tempat')->label('Kota/Kabupaten')->placeholder('-'),
                Infolists\Components\TextEntry::make('email')->placeholder('-'),
                Infolists\Components\TextEntry::make('kontak_wa')->label('WhatsApp')->placeholder('-'),
                Infolists\Components\TextEntry::make('alamat')->columnSpanFull()->placeholder('-'),
                Infolists\Components\TextEntry::make('nama_pemerintah')->label('Pemerintah')->placeholder('-'),
                Infolists\Components\TextEntry::make('nama_dinas')->label('Dinas')->placeholder('-'),
                Infolists\Components\TextEntry::make('nama_korwil')->label('Korwil')->placeholder('-'),
            ]),
            Infolists\Components\Section::make('Aktivitas & Langganan')->columns(4)->schema([
                Infolists\Components\TextEntry::make('transaksi_count')->label('Pengeluaran barang'),
                Infolists\Components\TextEntry::make('surat_digenerate_count')->label('Sudah di-generate'),
                Infolists\Components\TextEntry::make('draft_count')->label('Draft'),
                Infolists\Components\TextEntry::make('hak_aktif_count')->label('Triwulan aktif'),
                Infolists\Components\TextEntry::make('generate_percobaan_terpakai')
                    ->label('Kuota percobaan terpakai')
                    ->formatStateUsing(fn($state) => (int) $state . ' dari ' . (int) config('langganan.kuota_percobaan')),
                Infolists\Components\TextEntry::make('total_pembayaran')->label('Total pembayaran')->money('IDR')->placeholder('Rp0'),
                Infolists\Components\TextEntry::make('users_max_last_login_at')->label('Terakhir aktif')->dateTime('d-m-Y H:i')->placeholder('Belum pernah'),
                Infolists\Components\TextEntry::make('created_at')->label('Terdaftar')->dateTime('d-m-Y H:i'),
            ]),
            Infolists\Components\Section::make('User')->schema([
                Infolists\Components\RepeatableEntry::make('users')->label('')->columns(4)->schema([
                    Infolists\Components\TextEntry::make('name')->label('Nama'),
                    Infolists\Components\TextEntry::make('email'),
                    Infolists\Components\TextEntry::make('email_verified_at')->label('Email terverifikasi')->dateTime('d-m-Y')->placeholder('Belum'),
                    Infolists\Components\TextEntry::make('last_login_at')->label('Terakhir login')->dateTime('d-m-Y H:i')->placeholder('Belum pernah'),
                ]),
            ]),
            Infolists\Components\Section::make('Hak Triwulan')->schema([
                Infolists\Components\RepeatableEntry::make('hakTriwulan')->label('')->columns(5)->schema([
                    Infolists\Components\TextEntry::make('tahunAnggaran.tahun')->label('Tahun'),
                    Infolists\Components\TextEntry::make('triwulan')->formatStateUsing(fn($state) => 'TW' . $state),
                    Infolists\Components\TextEntry::make('sumber')->badge(),
                    Infolists\Components\TextEntry::make('status')->badge(),
                    Infolists\Components\TextEntry::make('berlaku_sampai')->label('Berlaku sampai')->date('d-m-Y')->placeholder('Tanpa batas'),
                ]),
            ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSekolahs::route('/'),
            'view' => Pages\ViewSekolah::route('/{record}'),
        ];
    }
}
