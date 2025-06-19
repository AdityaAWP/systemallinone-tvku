<?php

namespace App\Filament\Resources\InternDivisionResource\Pages;

use App\Filament\Resources\InternDivisionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInternDivisions extends ListRecords
{
    protected static string $resource = InternDivisionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
