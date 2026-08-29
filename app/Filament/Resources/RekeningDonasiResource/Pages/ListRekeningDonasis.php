<?php

namespace App\Filament\Resources\RekeningDonasiResource\Pages;

use App\Filament\Resources\RekeningDonasiResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRekeningDonasis extends ListRecords
{
    protected static string $resource = RekeningDonasiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
