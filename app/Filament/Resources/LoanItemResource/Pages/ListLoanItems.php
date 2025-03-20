<?php

namespace App\Filament\Resources\LoanItemResource\Pages;

use App\Filament\Resources\LoanItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLoanItems extends ListRecords
{
    protected static string $resource = LoanItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
