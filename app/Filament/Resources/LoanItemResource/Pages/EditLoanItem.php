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
    
    // Check access before editing
    protected function canEdit(): bool
    {
        $user = Auth::user();
        $record = $this->getRecord();
        
        // Allow edit if user is admin_logistics, super_admin, or the owner
        return $user->hasRole(['admin_logistics', 'super_admin']) || 
               $record->user_id === $user->id;
    }
    
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $loanItem = $this->getRecord()->load('user', 'items');
        
        // Set user fields
        $data['user']['name'] = $loanItem->user->name;
        
        // Set division value directly from the loan item
        // We don't need to retrieve it from user->division anymore
        
        // Set item quantities based on actual field names in your form
        $allItems = Item::all();
        foreach ($allItems as $item) {
            // Find if this item exists in the loan and get its quantity
            $quantity = $loanItem->items->firstWhere('id', $item->id)?->pivot->quantity ?? 0;
            // Set the quantity using the format that matches your form fields
            $data["item_{$item->id}_quantity"] = $quantity;
        }
        
        return $data;
    }
    
    protected function beforeSave(): void
    {
        $oldLoanItem = $this->getRecord()->load('items');
        $oldApprovalStatus = $oldLoanItem->approval_admin_logistics;
        $oldReturnStatus = $oldLoanItem->return_status;
        
        // Process approval status changes
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
        
        // Process return status changes
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
}