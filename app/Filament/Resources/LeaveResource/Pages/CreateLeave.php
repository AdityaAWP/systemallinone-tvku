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

        $totalLeavesThisMonth = $this->countLeavesInMonth($user, $month, $year);

        $specificTypeLeavesThisMonth = $this->countLeavesByTypeInMonth($user, $leaveType, $month, $year);

        // Berikan peringatan untuk semua jenis cuti kecuali medical
        if ($specificTypeLeavesThisMonth == 1 && $leaveType !== 'medical') {
            $leaveTypeName = $this->getLeaveTypeName($leaveType);
            FilamentNotification::make()
                ->title("Peringatan Penggunaan Cuti {$leaveTypeName}")
                ->body("Anda sudah mengambil 1 cuti {$leaveTypeName} di bulan ini. Anda masih bisa mengajukan 1 cuti {$leaveTypeName} lagi bulan ini.")
                ->warning()
                ->send();
        }

        // Batasan 2 kali per bulan untuk semua jenis cuti kecuali medical
        if ($specificTypeLeavesThisMonth >= 2 && $leaveType !== 'medical') {
            $leaveTypeName = $this->getLeaveTypeName($leaveType);
            FilamentNotification::make()
                ->title("Batas Cuti Bulanan Tercapai")
                ->body("Anda sudah mengambil 2 cuti {$leaveTypeName} di bulan ini. Anda tidak dapat mengambil cuti {$leaveTypeName} lagi bulan ini.")
                ->danger()
                ->persistent()
                ->send();

            $this->halt();
        }

        // Tidak ada batasan total cuti per bulan - hanya dibatasi per jenis cuti
        // Setiap jenis cuti (kecuali medical) maksimal 2 kali per bulan
        // Jadi user bisa: 2x casual + 2x other + unlimited medical per bulan
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
            $quota->casual_used += 1; // Tambah 1 kesempatan, bukan hari
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
            
            // Cek kesempatan cuti tahunan
            if ($quota->casual_used >= $quota->casual_quota) {
                FilamentNotification::make()
                    ->title('Kesempatan Cuti Tahunan Habis')
                    ->body('Anda telah habis kesempatan untuk mengajukan cuti tahunan tahun ini.')
                    ->danger()
                    ->persistent()
                    ->send();
                $this->halt();
            }

            // Hitung total hari cuti tahunan yang sudah digunakan tahun ini
            $currentYear = date('Y');
            $approvedCasualLeaves = Leave::where('user_id', $user->id)
                ->where('leave_type', 'casual')
                ->whereYear('from_date', $currentYear)
                ->where('status', 'approved')
                ->get();
            
            $usedDays = 0;
            foreach ($approvedCasualLeaves as $leave) {
                $usedDays += \App\Filament\Resources\LeaveResource::calculateWorkingDays($leave->from_date, $leave->to_date);
            }
            
            // Cek apakah hari yang diminta melebihi sisa kuota 12 hari per tahun
            if (($usedDays + $days) > 12) {
                $remainingDays = 12 - $usedDays;
                FilamentNotification::make()
                    ->title('Kuota Hari Cuti Tahunan Terlampaui')
                    ->body("Anda hanya memiliki {$remainingDays} hari cuti tahunan tersisa dari 12 hari per tahun. Hari yang diminta: {$days} hari.")
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
        // Kirim ke HRD
        $hrdUsers = User::role('hrd')->get();
        Notification::send($hrdUsers, new LeaveRequested($leave));

        // Kirim ke manager/kepala yang sesuai dengan divisi staff
        $staff = $leave->user;
        
        // Pertama, cek apakah staff memiliki manager yang ditugaskan manual
        if ($staff->manager_id) {
            $assignedManager = User::find($staff->manager_id);
            if ($assignedManager && $assignedManager->is_active) {
                Notification::send([$assignedManager], new LeaveRequested($leave));
                Log::info('Notifikasi cuti dikirim ke assigned manager: ' . $assignedManager->name);
            }
        }
        
        // Kedua, kirim ke manager/kepala berdasarkan divisi staff
        $staffDivisionIds = $staff->divisions()->pluck('divisions.id')->toArray();
        if (empty($staffDivisionIds) && $staff->division_id) {
            $staffDivisionIds = [$staff->division_id];
        }
        
        if (!empty($staffDivisionIds)) {
            // Cari manager/kepala yang mengelola divisi yang sama dengan staff
            $managersAndHeads = User::where('is_active', true)
                ->whereHas('roles', function ($query) {
                    $query->where('name', 'like', 'manager_%')
                          ->orWhere('name', 'like', 'kepala_%');
                })
                ->where(function ($query) use ($staffDivisionIds) {
                    $query->whereHas('divisions', function ($q) use ($staffDivisionIds) {
                        $q->whereIn('divisions.id', $staffDivisionIds);
                    })
                    ->orWhereIn('division_id', $staffDivisionIds);
                })
                ->get();
                
            if ($managersAndHeads->count() > 0) {
                Notification::send($managersAndHeads, new LeaveRequested($leave));
                Log::info('Notifikasi cuti dikirim ke manager/kepala divisi: ' . $managersAndHeads->pluck('name')->implode(', '));
            }
        }
        
        // Fallback: Jika tidak ada manager/kepala ditemukan berdasarkan divisi, 
        // cari berdasarkan role mapping (staff_it -> manager_it)
        $staffRole = $staff->roles->first()?->name;
        if ($staffRole && str_starts_with($staffRole, 'staff_')) {
            $managerRole = 'manager_' . substr($staffRole, 6);
            $kepalaRole = 'kepala_' . substr($staffRole, 6);
            
            // Cek manager role
            if (\Spatie\Permission\Models\Role::where('name', $managerRole)->exists()) {
                $managers = User::role($managerRole)->where('is_active', true)->get();
                if ($managers->count() > 0) {
                    Notification::send($managers, new LeaveRequested($leave));
                    Log::info('Notifikasi cuti dikirim ke manager berdasarkan role: ' . $managers->pluck('name')->implode(', '));
                }
            }
            
            // Cek kepala role
            if (\Spatie\Permission\Models\Role::where('name', $kepalaRole)->exists()) {
                $kepala = User::role($kepalaRole)->where('is_active', true)->get();
                if ($kepala->count() > 0) {
                    Notification::send($kepala, new LeaveRequested($leave));
                    Log::info('Notifikasi cuti dikirim ke kepala berdasarkan role: ' . $kepala->pluck('name')->implode(', '));
                }
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
