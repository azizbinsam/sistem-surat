<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BannerResource\Pages;
use App\Models\Banner;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Menu Banner di panel admin (doc/LANGGANAN.md §11): upload gambar desktop & ponsel dengan ukuran PERSIS,
 * pilih lokasi tayang, urutan (tarik-lepas di tabel), dan jadwal tayang opsional.
 */
class BannerResource extends Resource
{
    protected static ?string $model = Banner::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationLabel = 'Banner';

    protected static ?string $navigationGroup = 'Konten';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Banner';

    protected static ?string $pluralModelLabel = 'Banner';

    public static function form(Form $form): Form
    {
        [$lebarD, $tinggiD] = Banner::DESKTOP;
        [$lebarM, $tinggiM] = Banner::MOBILE;

        return $form->schema([
            Forms\Components\Section::make('Informasi')->schema([
                Forms\Components\TextInput::make('judul')
                    ->label('Judul (internal)')
                    ->helperText('Hanya untuk Anda mengenali banner ini; tidak tampil ke pengunjung.')
                    ->required()
                    ->maxLength(120),
                Forms\Components\TextInput::make('teks_alt')
                    ->label('Teks alternatif')
                    ->helperText('Deskripsi singkat isi gambar, dibaca oleh pembaca layar dan tampil bila gambar gagal dimuat.')
                    ->required()
                    ->maxLength(160),
                Forms\Components\TextInput::make('link_url')
                    ->label('Tautan (opsional)')
                    ->helperText('Awali dengan https:// atau http://, atau dengan satu garis miring untuk halaman di situs ini (contoh: /register). Kosongkan kalau banner tidak perlu diklik.')
                    ->maxLength(500)
                    // Array (bukan string "a|b") karena regex ini mengandung tanda | yang akan dianggap pemisah aturan oleh Laravel.
                    ->rules(['regex:/^(https?:\/\/|\/(?!\/))/i'])
                    ->validationMessages(['regex' => 'Tautan harus diawali https://, http://, atau satu garis miring (/).']),
            ])->columns(1),

            Forms\Components\Section::make('Gambar')
                ->description('Unggah DUA gambar supaya banner tampil pas di komputer dan di ponsel. Ukuran harus persis sama, kalau tidak akan ditolak.')
                ->schema([
                    Forms\Components\FileUpload::make('gambar_desktop')
                        ->label("Gambar desktop ({$lebarD} × {$tinggiD} px)")
                        ->helperText("Tampil di layar lebar (≥ 768 px). Tepat {$lebarD} × {$tinggiD} piksel, JPG/PNG/WebP, maksimal 1 MB.")
                        ->disk('public')
                        ->directory('banners')
                        ->visibility('public')
                        ->image()
                        // SVG sengaja tidak diizinkan: file SVG bisa menyisipkan skrip.
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->maxSize(1024)
                        ->rules(["dimensions:width={$lebarD},height={$tinggiD}"])
                        ->validationMessages(['dimensions' => "Ukuran gambar harus tepat {$lebarD} × {$tinggiD} piksel."])
                        ->imagePreviewHeight('100')
                        ->required(),
                    Forms\Components\FileUpload::make('gambar_mobile')
                        ->label("Gambar ponsel ({$lebarM} × {$tinggiM} px)")
                        ->helperText("Tampil di layar kecil (< 768 px). Tepat {$lebarM} × {$tinggiM} piksel, JPG/PNG/WebP, maksimal 1 MB.")
                        ->disk('public')
                        ->directory('banners')
                        ->visibility('public')
                        ->image()
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->maxSize(1024)
                        ->rules(["dimensions:width={$lebarM},height={$tinggiM}"])
                        ->validationMessages(['dimensions' => "Ukuran gambar harus tepat {$lebarM} × {$tinggiM} piksel."])
                        ->imagePreviewHeight('100')
                        ->required(),
                ])->columns(2),

            Forms\Components\Section::make('Tayang')->schema([
                Forms\Components\Select::make('lokasi')
                    ->options(Banner::LOKASI)
                    ->default('keduanya')
                    ->required(),
                Forms\Components\TextInput::make('urutan')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->helperText('Angka kecil tampil lebih dulu. Bisa juga diatur dengan tarik-lepas di daftar banner.'),
                Forms\Components\Toggle::make('aktif')->default(true)->helperText('Matikan untuk menyembunyikan banner tanpa menghapusnya.'),
                Forms\Components\DateTimePicker::make('tayang_mulai')->label('Mulai tayang (opsional)'),
                Forms\Components\DateTimePicker::make('tayang_selesai')
                    ->label('Selesai tayang (opsional)')
                    ->afterOrEqual('tayang_mulai'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('urutan')
            ->reorderable('urutan')
            ->columns([
                Tables\Columns\ImageColumn::make('gambar_desktop')->label('Pratinjau')->disk('public')->height(40)->width(160),
                Tables\Columns\TextColumn::make('judul')->searchable(),
                Tables\Columns\TextColumn::make('lokasi')
                    ->badge()
                    ->formatStateUsing(fn(string $state) => Banner::LOKASI[$state] ?? $state),
                Tables\Columns\TextColumn::make('status_tayang')
                    ->label('Status')
                    ->badge()
                    ->state(fn(Banner $record) => $record->statusTayang())
                    ->color(fn(string $state) => match ($state) {
                        'Tayang' => 'success',
                        'Terjadwal' => 'info',
                        'Berakhir' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\ToggleColumn::make('aktif'),
                Tables\Columns\TextColumn::make('urutan')->sortable(),
                Tables\Columns\TextColumn::make('tayang_mulai')->label('Mulai')->dateTime('d-m-Y H:i')->placeholder('-')->toggleable(),
                Tables\Columns\TextColumn::make('tayang_selesai')->label('Selesai')->dateTime('d-m-Y H:i')->placeholder('-')->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('lokasi')->options(Banner::LOKASI),
                Tables\Filters\TernaryFilter::make('aktif'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBanners::route('/'),
            'create' => Pages\CreateBanner::route('/create'),
            'edit' => Pages\EditBanner::route('/{record}/edit'),
        ];
    }
}
