<?php

namespace App\Filament\Resources\LogisticsResource\Pages;

use App\Filament\Resources\LogisticsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLogistics extends EditRecord
{
    protected static string $resource = LogisticsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
