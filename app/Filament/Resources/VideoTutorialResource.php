<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VideoTutorialResource\Pages;
use App\Models\VideoTutorial;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class VideoTutorialResource extends Resource
{
    protected static ?string $model = VideoTutorial::class;

    protected static ?string $navigationIcon = 'heroicon-o-play-circle';

    protected static ?string $navigationLabel = 'Video Tutorial';

    protected static ?string $navigationGroup = 'Pengaturan Platform';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('judul')
                ->required()
                ->maxLength(255),

            Forms\Components\Textarea::make('deskripsi')
                ->rows(3)
                ->maxLength(1000),

            Forms\Components\TextInput::make('url_youtube')
                ->label('Link YouTube')
                ->required()
                ->url()
                ->placeholder('https://www.youtube.com/watch?v=... atau https://youtu.be/...')
                ->helperText('Paste link video apa aja dari YouTube (watch, share, atau embed) — ID video diambil otomatis.')
                ->live()
                ->rule(function () {
                    return function (string $attribute, $value, \Closure $fail) {
                        if (!(new VideoTutorial(['url_youtube' => $value]))->youtube_id) {
                            $fail('Link YouTube tidak dikenali. Pastikan link video valid.');
                        }
                    };
                }),

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
                Tables\Columns\ImageColumn::make('thumbnail_url')->label('Thumbnail'),
                Tables\Columns\TextColumn::make('judul')->searchable(),
                Tables\Columns\TextColumn::make('deskripsi')->limit(50),
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
            'index' => Pages\ListVideoTutorials::route('/'),
            'create' => Pages\CreateVideoTutorial::route('/create'),
            'edit' => Pages\EditVideoTutorial::route('/{record}/edit'),
        ];
    }
}
