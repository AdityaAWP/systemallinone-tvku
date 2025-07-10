<?php

namespace App\Filament\Resources\LeaveResource\Pages;

use App\Filament\Resources\LeaveResource;
use App\Models\Leave;
use App\Models\LeaveQuota;
use App\Models\User;
use App\Notifications\LeaveStatusUpdated;
use Carbon\Carbon;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EditLeave extends EditRecord
{
    protected static string $resource = LeaveResource::class;

    // --- [FIX] Restored Helper Functions ---
    // These functions now have the correct 'return' statements.

    /**
     * Check if user has any kepala role
     */
    private function isKepala($user): bool
    {
        return $user->roles()->where('name', 'like', 'kepala%')->exists();
    }

    /**
     * Check if user has any manager role
     */
    private function isManager($user): bool
    {
        return $user->roles()->where('name', 'like', 'manager%')->exists();
    }

    /**
     * Check if user has HRD role
     */
    private function isHrd($user): bool
    {
        return $user->roles()->where('name', 'hrd')->exists();
    }

    /**
     * Check if user has any staff role
     */
    private function isStaff($user): bool
    {
        return $user->roles()->where('name', 'like', 'staff%')->exists();
    }
    // --- End of Fix ---

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
    
    /**
     * This hook is executed before the form data is saved to the database.
     * It's the perfect place for final validation.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = Auth::user();
        
        // We only run this validation if a staff member is editing their own leave
        // AND if they are actually changing the leave type.
        if ($this->isStaff($user) && $this->record->user_id === $user->id) {
            if ($this->record->leave_type !== $data['leave_type']) {
                $this->validateLeaveTypeChange($this->record->user, $data, $this->record);
            }
        }

        return $data;
    }

    /**
     * Validates if changing the leave type is allowed based on monthly limits.
     * This directly solves your problem.
     */
    protected function validateLeaveTypeChange(User $user, array $data, Leave $currentRecord): void
    {
        $newLeaveType = $data['leave_type'];
        $fromDate = Carbon::parse($data['from_date']);
        $month = $fromDate->month;
        $year = $fromDate->year;

        // Cuti 'casual' (tahunan) memiliki batasan maksimal 2 kali per bulan
        // Hanya cuti 'medical' yang tidak dibatasi
        $limitedTypes = ['casual', 'other']; // Kembalikan 'casual' ke limitedTypes
        
        if (!in_array($newLeaveType, $limitedTypes)) {
            // If changing to a type without a limit (e.g., medical), no validation needed.
            return;
        }

        $monthlyLimit = 2;
        $leaveTypeName = $this->getLeaveTypeName($newLeaveType);

        // Count how many leaves of the NEW type already exist in that month,
        // EXCLUDING the one we are currently editing.
        $existingLeavesCount = $this->countLeavesByTypeInMonth(
            $user,
            $newLeaveType,
            $month,
            $year,
            $currentRecord->id // Exclude this record from the count
        );

        // If the number of existing leaves already meets or exceeds the limit, block the change.
        if ($existingLeavesCount >= $monthlyLimit) {
            FilamentNotification::make()
                ->title("Batas Cuti Bulanan Tercapai")
                ->body("Gagal mengubah. Anda sudah memiliki {$existingLeavesCount} Cuti {$leaveTypeName} di bulan ini, yang merupakan batas maksimal.")
                ->danger()
                ->persistent()
                ->send();

            // Halt the save process
            $this->halt();
        }
    }


    /**
     * Counts leaves of a specific type in a given month for a user.
     * Includes an $excludeId parameter to ignore a specific record.
     */
    private function countLeavesByTypeInMonth(User $user, $leaveType, $month, $year, $excludeId = null): int
    {
        $query = Leave::where('user_id', $user->id)
            ->where('leave_type', $leaveType)
            ->whereMonth('from_date', $month)
            ->whereYear('from_date', $year)
            ->whereIn('status', ['pending', 'approved']);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->count();
    }

    private function getLeaveTypeName($type): string
    {
        $translations = [
            'casual' => 'Tahunan',
            'medical' => 'Sakit',
            'maternity' => 'Melahirkan',
            'other' => 'Lainnya'
        ];
        return $translations[$type] ?? $type;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $user = Auth::user();
        $originalStatus = $record->status;
        $originalLeaveType = $record->leave_type;
        $statusChanged = false;

        // Logic for approval status changes by HRD/Manager
        if ($this->isHrd($user) && isset($data['approval_hrd']) && $record->approval_hrd !== $data['approval_hrd']) {
            if ($data['approval_hrd'] === false) $data['status'] = 'rejected';
            elseif ($data['approval_hrd'] === true && ($record->approval_manager === true || $record->approval_manager === null)) $data['status'] = 'approved';
            $statusChanged = true;
        }
        if (($this->isManager($user) || $this->isKepala($user)) && isset($data['approval_manager']) && $record->approval_manager !== $data['approval_manager']) {
            if ($data['approval_manager'] === false) $data['status'] = 'rejected';
            elseif ($data['approval_manager'] === true && ($record->approval_hrd === true || $record->approval_hrd === null)) $data['status'] = 'approved';
            $statusChanged = true;
        }

        // Quota validation if staff is changing TO casual leave
        if ($this->isStaff($user) && $record->user_id === $user->id && $data['leave_type'] === 'casual' && $originalLeaveType !== 'casual') {
            $this->validateCasualLeaveQuota($record->user, $data);
        }
        
        $updatedRecord = parent::handleRecordUpdate($record, $data);

        // Update quota if leave type was successfully changed
       if (isset($data['leave_type']) && $originalLeaveType !== $data['leave_type']) {
            $this->updateLeaveQuotaAfterChange($originalLeaveType, $data['leave_type'], $record->user_id);
        }


        // Send notification if status changed
        if ($statusChanged && $updatedRecord->status !== $originalStatus) {
            try {
                $updatedRecord->user->notify(new LeaveStatusUpdated($updatedRecord));
                Log::info('Notifikasi status cuti dikirim ke: ' . $updatedRecord->user->email);
            } catch (\Exception $e) {
                Log::error('Gagal mengirim notifikasi: ' . $e->getMessage());
            }
        }

        return $updatedRecord;
    }

    private function validateCasualLeaveQuota(User $user, array $data): void
    {
        $quota = LeaveQuota::getUserQuota($user->id);
        $days = $data['days'] ?? 1;

        if (($quota->casual_used + $days) > $quota->casual_quota) {
            FilamentNotification::make()
                ->title('Kuota Cuti Tahunan Terlampaui')
                ->body('Anda telah melebihi kuota cuti tahunan Anda untuk tahun ini.')
                ->danger()
                ->persistent()
                ->send();
            $this->halt();
        }
    }
    
    private function updateLeaveQuotaAfterChange(string $originalType, string $newType, int $userId): void
    {
        $quota = LeaveQuota::getUserQuota($userId);
        
        // Revert quota from the original leave type if it was casual
        if ($originalType === 'casual') {
            $quota->casual_used = max(0, $quota->casual_used - 1);
        }

        // Apply quota for the new leave type if it's casual
        if ($newType === 'casual') {
            $quota->casual_used += 1;
        }

        $quota->save();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}