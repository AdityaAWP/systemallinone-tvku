<?php

namespace App\Filament\Resources\LeaveResource\Pages;

use App\Filament\Resources\LeaveResource;
use App\Models\Leave;
use App\Notifications\LeaveStatusUpdated;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EditLeave extends EditRecord
{
    protected static string $resource = LeaveResource::class;

    /**
     * Check if user has any kepala role
     */
    private function isKepala($user): bool
    {
        return $user->roles()->where('name', 'like', 'kepala%')->exists();
    }

    /**
     * Check if user has HRD role
     */
    private function isHrd($user): bool
    {
        return $user->roles()->where('name', 'hrd')->exists();
    }

    /**
     * Check if user has any manager role
     */
    private function isManager($user): bool
    {
        return $user->roles()->where('name', 'like', 'manager%')->exists();
    }

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
        if ($this->isHrd($user) && isset($data['approval_hrd']) && $record->approval_hrd !== $data['approval_hrd']) {
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

        // If user is Manager or Kepala and changed their approval
        if (($this->isManager($user) || $this->isKepala($user)) && isset($data['approval_manager']) && $record->approval_manager !== $data['approval_manager']) {
            // If Manager/Kepala rejected the leave
            if ($data['approval_manager'] === false) {
                $data['status'] = 'rejected';
                $statusChanged = true;
            }
            // If Manager/Kepala approved and HRD already approved (or no HRD approval needed)
            elseif ($data['approval_manager'] === true && ($record->approval_hrd === true || $record->approval_hrd === null)) {
                $data['status'] = 'approved';
                $statusChanged = true;
            }
        }

        // Update the record
        $updatedRecord = parent::handleRecordUpdate($record, $data);

        // Send notification if status changed
        if ($statusChanged && $updatedRecord->status !== $originalStatus) {
            try {
                // Kirim notifikasi ke staff yang mengajukan cuti
                $updatedRecord->user->notify(new LeaveStatusUpdated($updatedRecord));
                
                Log::info('Notifikasi status cuti dikirim ke: ' . $updatedRecord->user->email);
                
                FilamentNotification::make()
                    ->title('Notifikasi Terkirim')
                    ->body('Email notifikasi telah dikirim ke ' . $updatedRecord->user->email)
                    ->success()
                    ->send();
            } catch (\Exception $e) {
                Log::error('Gagal mengirim notifikasi: ' . $e->getMessage());
                FilamentNotification::make()
                    ->title('Gagal Mengirim Notifikasi')
                    ->body('Status cuti berhasil diupdate tetapi gagal mengirim email notifikasi')
                    ->danger()
                    ->send();
            }
        }

        return $updatedRecord;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}