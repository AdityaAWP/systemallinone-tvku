<?php

namespace App\Filament\Resources\FinancialAssignmentLetterResource\Pages;

use App\Filament\Resources\FinancialAssignmentLetterResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFinancialAssignmentLetters extends ListRecords
{
    protected static string $resource = FinancialAssignmentLetterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
