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
                ->hidden(fn ($record) =>
                    $record->approval_status !== $record::STATUS_PENDING ||
                    Auth::user()->hasRole('direktur_keuangan') ||
                    (Auth::user()->hasRole('staff_keuangan') && $record->created_by !== Auth::id())),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $record = $this->getRecord();
        
        // Check if approval status has changed and user has permission to approve
        if (Auth::user()->hasAnyRole(['direktur_keuangan', 'direktur_utama']) &&
            $record->approval_status !== $data['approval_status'] &&
            in_array($data['approval_status'], [Assignment::STATUS_APPROVED, Assignment::STATUS_DECLINED])) {
            
            $data['approved_by'] = Auth::id();
            $data['approved_at'] = now();
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord();
        
        if (Auth::user()->hasAnyRole(['direktur_keuangan', 'direktur_utama']) &&
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