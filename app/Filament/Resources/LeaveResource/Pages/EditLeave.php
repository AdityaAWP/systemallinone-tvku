<?php

namespace App\Filament\Resources\LeaveResource\Pages;

use App\Filament\Resources\LeaveResource;
use App\Models\Leave;
use App\Notifications\LeaveStatusUpdated;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EditLeave extends EditRecord
{
    protected static string $resource = LeaveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $user = Auth::user();
        $originalStatus = $record->status;
        $statusChanged = false;

        // If user is HRD and changed their approval
        if ($user->hasRole('hrd') && isset($data['approval_hrd']) && $record->approval_hrd !== $data['approval_hrd']) {
            // If HRD rejected the leave
            if ($data['approval_hrd'] === false) {
                $data['status'] = 'rejected';
                $statusChanged = true;
            }
            // If HRD approved and Manager already approved (or no manager approval needed)
            elseif ($data['approval_hrd'] === true && ($record->approval_manager === true || $record->approval_manager === null)) {
                $data['status'] = 'approved';
                $statusChanged = true;
            }
        }

        // If user is Manager and changed their approval
        if ($user->hasRole('manager') && isset($data['approval_manager']) && $record->approval_manager !== $data['approval_manager']) {
            // If Manager rejected the leave
            if ($data['approval_manager'] === false) {
                $data['status'] = 'rejected';
                $statusChanged = true;
            }
            // If Manager approved and HRD already approved (or no HRD approval needed)
            elseif ($data['approval_manager'] === true && ($record->approval_hrd === true || $record->approval_hrd === null)) {
                $data['status'] = 'approved';
                $statusChanged = true;
            }
        }

        // Update the record
        $updatedRecord = parent::handleRecordUpdate($record, $data);

        // Send notification if status changed
        if ($statusChanged && $updatedRecord->status !== $originalStatus) {
            $updatedRecord->user->notify(new LeaveStatusUpdated($updatedRecord));
            
            // If leave was rejected and was a casual leave, refund the quota
            if ($updatedRecord->status === 'rejected' && $updatedRecord->leave_type === 'casual') {
                $quota = $updatedRecord->user->getCurrentYearQuota();
                $quota->casual_used = max(0, $quota->casual_used - 1);
                $quota->save();
            }
            
            // Show a notification in the UI
            FilamentNotification::make()
                ->title('Leave status updated')
                ->body('The user has been notified of the status change.')
                ->success()
                ->send();
        }

        return $updatedRecord;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}