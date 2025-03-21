<?php

namespace App\Filament\Resources\LeaveResource\Pages;

use App\Filament\Resources\LeaveResource;
use App\Models\Leave;
use App\Models\LeaveQuota;
use App\Models\User;
use App\Notifications\LeaveRequested;
use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class CreateLeave extends CreateRecord
{
    protected static string $resource = LeaveResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = 'pending';
        
        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $user = User::find($data['user_id']);
        
        // Validate leave application
        $this->validateLeaveApplication($user, $data);
        
        // Create the leave record
        $leave = parent::handleRecordCreation($data);
        
        // Increment quota usage if casual leave
        if ($data['leave_type'] === 'casual') {
            $quota = LeaveQuota::getUserQuota($user->id);
            $quota->casual_used += 1;
            $quota->save();
        }
        
        // Send notifications to HRD and Manager
        $this->sendLeaveRequestNotifications($leave);
        
        return $leave;
    }

    private function validateLeaveApplication(User $user, array $data): void
    {
        $fromDate = Carbon::parse($data['from_date']);
        $month = $fromDate->month;
        $year = $fromDate->year;
        
        // Check if reached monthly limit (2 times per month unless maternity)
        if ($data['leave_type'] !== 'maternity' && $user->hasReachedMonthlyLeaveLimit($month, $year)) {
            $this->halt('You have already applied for leave twice this month. You can only apply for maternity leave now.');
        }
        
        // Check casual leave quota
        if ($data['leave_type'] === 'casual') {
            $quota = LeaveQuota::getUserQuota($user->id);
            
            if ($quota->casual_used >= $quota->casual_quota) {
                $this->halt('You have exhausted your casual leave quota for this year.');
            }
        }
    }

    private function sendLeaveRequestNotifications(Leave $leave): void
    {
        // Notify all HRD users
        $hrdUsers = User::role('HRD')->get();
        Notification::send($hrdUsers, new LeaveRequested($leave));
        
        // Notify manager
        $managers = User::role('Manager')->get();
        Notification::send($managers, new LeaveRequested($leave));
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}