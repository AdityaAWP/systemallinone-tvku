<?php

namespace App\Filament\Resources\InternSupervisorResource\Pages;

use App\Filament\Resources\InternSupervisorResource;
use Filament\Resources\Pages\ListRecords;

class ListInternSupervisors extends ListRecords
{
    protected static string $resource = InternSupervisorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action for read-only resource
        ];
    }
}
