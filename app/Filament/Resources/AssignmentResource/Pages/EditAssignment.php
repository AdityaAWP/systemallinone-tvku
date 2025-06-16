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
                    Auth::user()->hasRole('direktur_utama') ||
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
        $user = Auth::user();

        // Handle submission status change by manager_keuangan
        if ($user->hasRole('manager_keuangan') &&
            isset($data['submit_status']) && // Add this check
            $record->submit_status !== $data['submit_status'] &&
            $data['submit_status'] === Assignment::SUBMIT_SUDAH) {
            
            $data['submitted_by'] = Auth::id();
            $data['submitted_at'] = now();
        }

        // Handle approval status change by direktur_utama or direktur_utama
        if ($user->hasAnyRole(['direktur_utama']) &&
            isset($data['approval_status']) && // Add this check
            $record->approval_status !== $data['approval_status'] &&
            in_array($data['approval_status'], [Assignment::STATUS_APPROVED, Assignment::STATUS_DECLINED])) {
            
            // Only allow approval if assignment is submitted
            if ($record->submit_status === Assignment::SUBMIT_SUDAH) {
                $data['approved_by'] = Auth::id();
                $data['approved_at'] = now();
            } else {
                // Reset approval status if assignment is not submitted
                $data['approval_status'] = Assignment::STATUS_PENDING;
                Notification::make()
                    ->warning()
                    ->title('Cannot approve unsubmitted assignment')
                    ->body('Assignment must be submitted by manager before it can be approved.')
                    ->send();
            }
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord();
        $user = Auth::user();

        // Notification for submission
        if ($user->hasRole('manager_keuangan') && $record->submit_status === Assignment::SUBMIT_SUDAH) {
            Notification::make()
                ->title('Assignment Submitted')
                ->body("Assignment for {$record->client} has been submitted for approval.")
                ->success()
                ->send();
        }

        // Notification for approval/decline
        if ($user->hasAnyRole(['direktur_utama']) &&
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