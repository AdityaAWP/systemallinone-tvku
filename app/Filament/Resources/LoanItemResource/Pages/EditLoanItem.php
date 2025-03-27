<?php

namespace App\Filament\Resources\LoanItemResource\Pages;

use App\Filament\Resources\LoanItemResource;
use App\Models\Item;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditLoanItem extends EditRecord
{
    protected static string $resource = LoanItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $loanItem = $this->getRecord()->load('user', 'items');
        
        $data['user']['name'] = $loanItem->user->name;
        $data['user']['division'] = $loanItem->user->division['name'] ?? '';
        
        $allItems = Item::all();
        $itemsByCategory = $allItems->groupBy('category');
        
        foreach ($itemsByCategory as $category => $categoryItems) {
            $itemCount = $categoryItems->count();
            $midPoint = ceil($itemCount / 2);
            
            $leftItems = $categoryItems->take($midPoint);
            $rightItems = $categoryItems->slice($midPoint);
            
            foreach ($leftItems as $item) {
                $quantity = $loanItem->items->firstWhere('id', $item->id)?->pivot->quantity ?? 0;
                $data["left_item_{$item->id}_quantity"] = $quantity;
            }
            
            foreach ($rightItems as $item) {
                $quantity = $loanItem->items->firstWhere('id', $item->id)?->pivot->quantity ?? 0;
                $data["right_item_{$item->id}_quantity"] = $quantity;
            }
        }
        
        return $data;
    }
    
    protected function beforeSave(): void
    {
        $oldLoanItem = $this->getRecord()->load('items');
        $oldApprovalStatus = $oldLoanItem->approval_status;
        $oldReturnStatus = $oldLoanItem->return_status;
        
        // Process approval status changes
        if ($oldApprovalStatus !== $this->data['approval_status']) {
            $this->getRecord()->processStockChanges($oldApprovalStatus, $this->data['approval_status']);
        }
        
        // Process return status changes
        if ($oldReturnStatus !== $this->data['return_status']) {
            $this->getRecord()->processReturnStatusChanges($oldReturnStatus, $this->data['return_status']);
        }
    }
    
    protected function afterSave(): void
    {
        $items = [];
        foreach ($this->data as $key => $value) {
            if (str_contains($key, '_quantity') && $value > 0) {
                $itemId = str_replace(['left_item_', 'right_item_', '_quantity'], '', $key);
                $items[$itemId] = ['quantity' => $value];
            }
        }
        
        $this->getRecord()->items()->sync($items);
    }
}