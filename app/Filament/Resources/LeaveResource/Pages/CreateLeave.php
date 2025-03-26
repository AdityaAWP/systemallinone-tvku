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
use Filament\Notifications\Notification as FilamentNotification;

class CreateLeave extends CreateRecord
{
    protected static string $resource = LeaveResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = 'pending';
        
        return $data;
    }

    protected function beforeCreate(): void
    {
        $data = $this->form->getState();
        $user = User::find($data['user_id']);
        $this->validateMonthlyLeaveLimit($user, $data);
    }

    protected function validateMonthlyLeaveLimit(User $user, array $data): void
    {
        $fromDate = Carbon::parse($data['from_date']);
        $month = $fromDate->month;
        $year = $fromDate->year;
        $leaveType = $data['leave_type'];
  
        if ($leaveType === 'maternity') {
            return;
        }

        $totalLeavesThisMonth = $this->countLeavesInMonth($user, $month, $year);
     
        $specificTypeLeavesThisMonth = $this->countLeavesByTypeInMonth($user, $leaveType, $month, $year);
      
        if ($specificTypeLeavesThisMonth == 1) {
            $leaveTypeName = $this->getLeaveTypeName($leaveType);
            FilamentNotification::make()
                ->title("Peringatan Penggunaan Cuti {$leaveTypeName}")
                ->body("Anda sudah mengambil 1 cuti {$leaveTypeName} di bulan ini. Ini adalah cuti {$leaveTypeName} terakhir yang dapat Anda ambil bulan ini.")
                ->warning()
                ->send();
        }
      
        if ($specificTypeLeavesThisMonth >= 2) {
            $leaveTypeName = $this->getLeaveTypeName($leaveType);
            FilamentNotification::make()
                ->title("Batas Cuti Bulanan Tercapai")
                ->body("Anda sudah mengambil 2 cuti {$leaveTypeName} di bulan ini. Anda tidak dapat mengambil cuti {$leaveTypeName} lagi bulan ini.")
                ->danger()
                ->persistent()
                ->send();
                
            $this->halt("Anda sudah mencapai batas maksimal 2 cuti {$leaveTypeName} dalam bulan ini.");
        }
      
        if ($totalLeavesThisMonth >= 2) {
            FilamentNotification::make()
                ->title("Batas Total Cuti Bulanan Tercapai")
                ->body("Anda sudah mengajukan 2 cuti di bulan ini. Anda hanya dapat mengajukan cuti melahirkan sekarang.")
                ->danger()
                ->persistent()
                ->send();
                
            $this->halt("Anda sudah mencapai batas maksimal 2 cuti dalam bulan ini. Anda hanya dapat mengajukan cuti melahirkan sekarang.");
        }
    }

    protected function handleRecordCreation(array $data): Model
    {
        $user = User::find($data['user_id']);
       
        $this->validateLeaveApplication($user, $data);
      
        $leave = parent::handleRecordCreation($data);

        if ($data['leave_type'] === 'casual') {
            $quota = LeaveQuota::getUserQuota($user->id);
            $quota->casual_used += 1;
            $quota->save();
        }
      
        $this->sendLeaveRequestNotifications($leave);
      
        FilamentNotification::make()
            ->title('Permintaan cuti berhasil dibuat')
            ->body('Notifikasi telah dikirim ke HRD dan Manajer untuk ditinjau.')
            ->success()
            ->send();
            
        return $leave;
    }

    private function validateLeaveApplication(User $user, array $data): void
    {
        $fromDate = Carbon::parse($data['from_date']);
        $month = $fromDate->month;
        $year = $fromDate->year;
   
        if ($data['leave_type'] === 'casual') {
            $quota = LeaveQuota::getUserQuota($user->id);
            $days = $data['days'] ?? 1;
            
            if (($quota->casual_used + $days) > $quota->casual_quota) {
                $this->halt('Anda telah melebihi kuota cuti tahunan Anda untuk tahun ini.');
            }
        }
    }

    private function countLeavesInMonth(User $user, $month, $year): int
    {
        return Leave::where('user_id', $user->id)
            ->where('leave_type', '!=', 'maternity')
            ->whereMonth('from_date', $month)
            ->whereYear('from_date', $year)
            ->whereIn('status', ['pending', 'approved'])
            ->count();
    }

    private function countLeavesByTypeInMonth(User $user, $leaveType, $month, $year): int
    {
        return Leave::where('user_id', $user->id)
            ->where('leave_type', $leaveType)
            ->whereMonth('from_date', $month)
            ->whereYear('from_date', $year)
            ->whereIn('status', ['pending', 'approved'])
            ->count();
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

    private function sendLeaveRequestNotifications(Leave $leave): void
    {
        $hrdUsers = User::role('hrd')->get();
        Notification::send($hrdUsers, new LeaveRequested($leave));
        
        $managers = User::role('manager')->get();
        Notification::send($managers, new LeaveRequested($leave));
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}