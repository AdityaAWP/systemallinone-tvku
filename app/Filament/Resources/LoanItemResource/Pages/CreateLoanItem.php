<?php

namespace App\Filament\Resources\LoanItemResource\Pages;

use App\Filament\Resources\LoanItemResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateLoanItem extends CreateRecord
{
    protected static string $resource = LoanItemResource::class;
    protected function afterCreate(): void
    {
        $loan = $this->record;
        
        foreach ($loan->items as $item) {
            $quantity = $item->pivot->quantity;
            
            $item->update([
                'stock' => $item->stock - $quantity
            ]);
        }
    }

}
