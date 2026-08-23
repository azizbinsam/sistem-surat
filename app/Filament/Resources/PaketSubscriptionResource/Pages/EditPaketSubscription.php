<?php

namespace App\Filament\Resources\PaketSubscriptionResource\Pages;

use App\Filament\Resources\PaketSubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPaketSubscription extends EditRecord
{
    protected static string $resource = PaketSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
