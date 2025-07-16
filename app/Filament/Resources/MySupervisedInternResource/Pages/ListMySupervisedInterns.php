<?php

namespace App\Filament\Resources\MySupervisedInternResource\Pages;

use App\Filament\Resources\MySupervisedInternResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMySupervisedInterns extends ListRecords
{
    protected static string $resource = MySupervisedInternResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
