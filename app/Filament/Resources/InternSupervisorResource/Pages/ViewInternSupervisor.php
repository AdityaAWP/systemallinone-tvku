<?php

namespace App\Filament\Resources\InternSupervisorResource\Pages;

use App\Filament\Resources\InternSupervisorResource;
use Filament\Resources\Pages\ViewRecord;

class ViewInternSupervisor extends ViewRecord
{
    protected static string $resource = InternSupervisorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No edit/delete actions for read-only resource
        ];
    }
}
