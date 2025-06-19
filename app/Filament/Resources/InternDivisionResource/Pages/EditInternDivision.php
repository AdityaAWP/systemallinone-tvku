<?php

namespace App\Filament\Resources\InternDivisionResource\Pages;

use App\Filament\Resources\InternDivisionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInternDivision extends EditRecord
{
    protected static string $resource = InternDivisionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
