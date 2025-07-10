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
            Actions\DeleteAction::make()
                ->visible(fn () => Auth::user()->hasRole(['admin_logistics', 'super_admin'])),
        ];
    }
    
    protected function canEdit(): bool
    {
        $user = Auth::user();
        $record = $this->getRecord();
        
        if ($user->hasRole(['admin_logistik', 'super_admin'])) {
            return true;
        }

        if ($record->user_id === $user->id && !$record->approval_admin_logistics) {
            return true;
        }

        return false;
    }
    
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $loanItem = $this->getRecord()->load('user', 'items');
        
        $data['user']['name'] = $loanItem->user->name;
        
        $allItems = Item::all();
        foreach ($allItems as $item) {
            $quantity = $loanItem->items->firstWhere('id', $item->id)?->pivot->quantity ?? 0;
            $data["item_{$item->id}_quantity"] = $quantity;
        }
        
        return $data;
    }
    
    protected function beforeSave(): void
    {
        $oldLoanItem = $this->getRecord()->load('items');
        $oldApprovalStatus = $oldLoanItem->approval_admin_logistics;
        $oldReturnStatus = $oldLoanItem->return_status;
        
        if ($oldApprovalStatus !== $this->data['approval_admin_logistics']) {
            if ($this->data['approval_admin_logistics']) {
                foreach ($this->record->items as $item) {
                    $item->decreaseStock($item->pivot->quantity);
                }
            } else {
                foreach ($this->record->items as $item) {
                    $item->increaseStock($item->pivot->quantity);
                }
            }
        }
        
        if ($oldReturnStatus !== $this->data['return_status']) {
            $this->getRecord()->processReturnStatusChanges($oldReturnStatus, $this->data['return_status']);
        }
    }
    
    protected function afterSave(): void
    {
        $items = [];
        foreach ($this->data as $key => $value) {
            if (preg_match('/item_(\d+)_quantity/', $key, $matches) && $value > 0) {
                $itemId = $matches[1];
                $items[$itemId] = ['quantity' => $value];
            }
        }
        
        $this->getRecord()->items()->sync($items);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}