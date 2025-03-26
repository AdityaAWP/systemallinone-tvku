<?php

namespace App\Filament\Resources\LoanItemResource\Pages;

use App\Filament\Resources\LoanItemResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateLoanItem extends CreateRecord
{
    protected static string $resource = LoanItemResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return [...$data, 'user_id' => Auth::id()];
    }
    protected function afterCreate(): void
    {
        // Get all the items with quantities from the form data
        $items = [];
        foreach ($this->data as $key => $value) {
            if (str_contains($key, '_quantity') && $value > 0) {
                $itemId = str_replace(['left_item_', 'right_item_', '_quantity'], '', $key);
                $items[$itemId] = ['quantity' => $value];
            }
        }

        // Attach items to the loan item
        $this->record->items()->sync($items);
    }

}
