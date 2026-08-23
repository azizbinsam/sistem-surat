<?php

namespace App\Filament\Resources\PaketSubscriptionResource\Pages;

use App\Filament\Resources\PaketSubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPaketSubscriptions extends ListRecords
{
    protected static string $resource = PaketSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
