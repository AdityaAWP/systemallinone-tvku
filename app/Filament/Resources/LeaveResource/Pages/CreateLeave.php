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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Filament\Notifications\Notification as FilamentNotification;

class CreateLeave extends CreateRecord
{
    protected static string $resource = LeaveResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = 'pending';
        // Always set user_id to the authenticated user (scalar, force int)
        $data['user_id'] = (int) Auth::id();
        // Generate token unik setiap kali membuat permintaan cuti baru
        $data['approval_token'] = Str::random(64);
        Log::info('Token approval dibuat: ' . $data['approval_token']);
        // Set to_date to from_date if not provided
        if (empty($data['to_date'])) {
            $data['to_date'] = $data['from_date'] ?? null;
        }
        return $data;
    }

    protected function beforeCreate(): void
    {
        $data = $this->form->getState();
        $userId = $data['user_id'] ?? Auth::id();
        $user = User::find($userId);
        if (!$user instanceof User) {
            FilamentNotification::make()
                ->title('User tidak ditemukan')
                ->body('Data user tidak valid. Silakan login ulang.')
                ->danger()
                ->persistent()
                ->send();
            $this->halt();
        }
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

        // Special validation for casual leave (cuti tahunan)
        if ($leaveType === 'casual') {
            if (!$this->canCreateCasualLeaveForMonth($user, $month, $year)) {
                FilamentNotification::make()
                    ->title("Batas Cuti Tahunan Bulanan Tercapai")
                    ->body("Sudah 2 kali cuti tahunan untuk bulan ini")
                    ->danger()
                    ->persistent()
                    ->send();
                $this->halt();
            }

            $casualLeavesThisMonth = Leave::countCasualLeavesInMonth($user->id, $month, $year);
            if ($casualLeavesThisMonth == 1) {
                FilamentNotification::make()
                    ->title("Peringatan Penggunaan Cuti Tahunan")
                    ->body("Anda sudah mengambil 1 cuti tahunan di bulan ini. Ini adalah cuti tahunan terakhir yang dapat Anda ambil bulan ini.")
                    ->warning()
                    ->send();
            }
            return;
        }

        // Original logic for other leave types
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
            $this->halt();
        }

        if ($totalLeavesThisMonth >= 2) {
            FilamentNotification::make()
                ->title("Batas Total Cuti Bulanan Tercapai")
                ->body("Anda sudah mengajukan 2 cuti di bulan ini. Anda hanya dapat mengajukan cuti melahirkan sekarang.")
                ->danger()
                ->persistent()
                ->send();
            $this->halt();
        }
    }

    protected function handleRecordCreation(array $data): Model
    {
        $userId = $data['user_id'] ?? Auth::id();
        $user = User::find($userId);
        if (!$user instanceof User) {
            FilamentNotification::make()
                ->title('User tidak ditemukan')
                ->body('Data user tidak valid. Silakan login ulang.')
                ->danger()
                ->persistent()
                ->send();
            $this->halt();
        }
        $this->validateLeaveApplication($user, $data);

        $leave = parent::handleRecordCreation($data);

        if ($data['leave_type'] === 'casual') {
            $quota = LeaveQuota::getUserQuota($user->id);
            $quota->casual_used += 1;
            $quota->save();
        }

        // Kirim notifikasi dengan token di dalamnya
        $this->sendLeaveRequestNotifications($leave);

        Log::info('Permintaan cuti dibuat dengan ID: ' . $leave->id . ' dan token: ' . $leave->approval_token);

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
                FilamentNotification::make()
                    ->title('Kuota Cuti Tahunan Terlampaui')
                    ->body('Anda telah melebihi kuota cuti tahunan Anda untuk tahun ini.')
                    ->danger()
                    ->persistent()
                    ->send();
                $this->halt();
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

        // Get the staff's role and convert to manager role
        $staffRole = $leave->user->roles->first()?->name;
        if ($staffRole && str_starts_with($staffRole, 'staff_')) {
            $managerRole = 'manager_' . substr($staffRole, 6);
            // Cek apakah role manager ada
            if (\Spatie\Permission\Models\Role::where('name', $managerRole)->exists()) {
                $managers = User::role($managerRole)->get();
                Notification::send($managers, new LeaveRequested($leave));
            }
        }
    }

    /**
     * Check if user can create casual leave for the given month
     */
    private function canCreateCasualLeaveForMonth(User $user, $month, $year): bool
    {
        $casualLeavesThisMonth = Leave::countCasualLeavesInMonth($user->id, $month, $year);
        return $casualLeavesThisMonth < 2;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
