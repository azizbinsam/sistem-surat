<?php

namespace App\Filament\Resources\VideoTutorialResource\Pages;

use App\Filament\Resources\VideoTutorialResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVideoTutorials extends ListRecords
{
    protected static string $resource = VideoTutorialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
