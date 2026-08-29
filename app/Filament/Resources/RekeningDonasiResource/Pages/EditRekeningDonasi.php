<?php

namespace App\Filament\Resources\RekeningDonasiResource\Pages;

use App\Filament\Resources\RekeningDonasiResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRekeningDonasi extends EditRecord
{
    protected static string $resource = RekeningDonasiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
