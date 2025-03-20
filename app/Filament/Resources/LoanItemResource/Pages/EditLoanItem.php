<?php

namespace App\Filament\Resources\LoanItemResource\Pages;

use App\Filament\Resources\LoanItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLoanItem extends EditRecord
{
    protected static string $resource = LoanItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
