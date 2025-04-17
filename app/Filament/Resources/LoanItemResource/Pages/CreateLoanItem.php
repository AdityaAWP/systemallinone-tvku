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
            if (preg_match('/item_(\d+)_quantity/', $key, $matches) && $value > 0) {
                $itemId = $matches[1];
                $item = Item::find($itemId);
                if (!$item) {
                    continue;
                }
                if (!$item->hasStock($value)) {
                    throw new \Exception("Not enough stock for {$item->name}");
                }
                $items[$itemId] = ['quantity' => $value];
            }
        }
        
        // Only sync items if we found valid ones
        if (!empty($items)) {
            $this->record->items()->sync($items);
        }
        
        // Notify logistics admin
        $this->record->notifyLogisticsAdmin();
    }
}