<?php

namespace App\Filament\Resources\AssignmentResource\Pages;

use App\Filament\Resources\AssignmentResource;
use App\Models\Assignment;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditAssignment extends EditRecord
{
    protected static string $resource = AssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->hidden(fn (Assignment $record) => 
                    $record->approval_status !== Assignment::STATUS_PENDING || 
                    Auth::user()->hasRole('direktur_keuangan')),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord();
        
        // Only notify for director actions (approval/rejection)
        if (Auth::user()->hasRole('direktur_keuangan') && 
            in_array($record->approval_status, [Assignment::STATUS_APPROVED, Assignment::STATUS_DECLINED])) {
            
            $status = $record->approval_status === Assignment::STATUS_APPROVED ? 'approved' : 'declined';
            
            Notification::make()
                ->title("Assignment {$status}")
                ->body("The assignment for {$record->client} has been {$status}.")
                ->success()
                ->send();
        }
    }
}