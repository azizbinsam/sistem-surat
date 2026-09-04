<?php

namespace App\Filament\Resources\VideoTutorialResource\Pages;

use App\Filament\Resources\VideoTutorialResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVideoTutorial extends EditRecord
{
    protected static string $resource = VideoTutorialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
