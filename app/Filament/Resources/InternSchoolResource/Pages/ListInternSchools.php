<?php

namespace App\Filament\Resources\InternSchoolResource\Pages;

use App\Filament\Resources\InternSchoolResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInternSchools extends ListRecords
{
    protected static string $resource = InternSchoolResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
