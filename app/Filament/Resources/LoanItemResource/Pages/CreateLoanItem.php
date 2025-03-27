<?php

namespace App\Filament\Resources\LoanItemResource\Pages;

use App\Filament\Resources\LoanItemResource;
use App\Models\Item;
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
        $items = [];
        foreach ($this->data as $key => $value) {
            if (str_contains($key, '_quantity') && $value > 0) {
                $itemId = str_replace(['left_item_', 'right_item_', '_quantity'], '', $key);
                
                // Get the item
                $item = Item::find($itemId);
                
                // Validate stock
                if (!$item->hasStock($value)) {
                    throw new \Exception("Not enough stock for {$item->name}");
                }
                
                $items[$itemId] = ['quantity' => $value];
            }
        }

        $this->record->items()->sync($items);
        
        // If approval status is Approve, decrease stock
        if ($this->record->approval_status === 'Approve') {
            foreach ($this->record->items as $item) {
                $item->decreaseStock($item->pivot->quantity);
            }
        }
    }
}