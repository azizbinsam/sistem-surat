<?php

namespace App\Filament\Pages;

use App\Models\AppSettings;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class PengaturanAplikasi extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Pengaturan Aplikasi';

    protected static ?string $navigationGroup = 'Pengaturan Platform';

    protected static string $view = 'filament.pages.pengaturan-aplikasi';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = AppSettings::current();

        $this->form->fill($settings->only(['nama_aplikasi', 'logo_aplikasi']));
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('nama_aplikasi')
                ->label('Nama Aplikasi')
                ->required()
                ->maxLength(255)
                ->helperText('Ditampilkan di landing page dan dashboard sekolah.'),

            Forms\Components\FileUpload::make('logo_aplikasi')
                ->label('Logo Aplikasi')
                ->image()
                ->directory('app-settings')
                ->disk('public'),
        ])->statePath('data');
    }

    public function simpan(): void
    {
        $settings = AppSettings::current();
        $settings->update($this->form->getState());

        Notification::make()
            ->title('Pengaturan aplikasi berhasil disimpan')
            ->success()
            ->send();
    }
}
