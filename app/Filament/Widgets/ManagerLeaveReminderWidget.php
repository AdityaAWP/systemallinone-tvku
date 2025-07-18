<?php

namespace App\Filament\Widgets;

use App\Models\Leave;
use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class ManagerLeaveReminderWidget extends Widget
{
    protected static string $view = 'filament.widgets.manager-leave-reminder-widget';
    
    protected int | string | array $columnSpan = 'full';

    public function getStaffLeaveData()
    {
        // Get current manager
        $manager = Auth::user();
        
        // Cek apakah user memiliki role manager atau kepala
        $isManager = $manager->roles()->where('name', 'like', 'manager%')->exists();
        $isKepala = $manager->roles()->where('name', 'like', 'kepala%')->exists();
        
        if (!$isManager && !$isKepala) {
            return collect();
        }
        
        // Get staff yang dikelola oleh manager/kepala ini
        $staffQuery = User::where('is_active', true);
        
        // 1. Staff yang memiliki manager_id = manager ini
        $directStaffIds = User::where('manager_id', $manager->id)
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();
        
        // 2. Staff dari divisi yang sama (menggunakan many-to-many divisions)
        $managerDivisionIds = $manager->divisions()->pluck('divisions.id')->toArray();
        if (empty($managerDivisionIds) && $manager->division_id) {
            $managerDivisionIds = [$manager->division_id];
        }
        
        $divisionStaffIds = [];
        if (!empty($managerDivisionIds)) {
            $divisionStaffIds = User::where('is_active', true)
                ->where(function ($query) use ($managerDivisionIds) {
                    $query->whereHas('divisions', function ($q) use ($managerDivisionIds) {
                        $q->whereIn('divisions.id', $managerDivisionIds);
                    })
                    ->orWhereIn('division_id', $managerDivisionIds);
                })
                ->whereHas('roles', function ($query) {
                    $query->where('name', 'like', 'staff_%');
                })
                ->pluck('id')
                ->toArray();
        }
        
        // Gabungkan staff dari kedua sumber
        $allStaffIds = array_unique(array_merge($directStaffIds, $divisionStaffIds));
        
        $currentYear = Carbon::now()->year;
        $staffLeaveData = [];
        
        foreach ($allStaffIds as $staffId) {
            $staff = User::find($staffId);
            if (!$staff || $staff->id === $manager->id) continue;
            
            // Exclude super admin, hrd, dan manager lainnya
            if ($staff->hasRole('super_admin') || $staff->hasRole('hrd')) continue;
            if ($staff->roles()->where('name', 'like', 'manager%')->exists()) continue;
            if ($staff->roles()->where('name', 'like', 'kepala%')->exists()) continue;
            
            // Get quota information
            $quota = $staff->getCurrentYearQuota();
            
            // Get medical leaves count
            $medicalLeaves = Leave::where('user_id', $staffId)
                ->where('leave_type', 'medical')
                ->whereYear('from_date', $currentYear)
                ->where('status', 'approved')
                ->count();
            
            // Get pending leaves
            $pendingLeaves = Leave::where('user_id', $staffId)
                ->where('status', 'pending')
                ->count();
            
            // Get upcoming leaves (next 7 days)
            $upcomingLeaves = Leave::where('user_id', $staffId)
                ->where('status', 'approved')
                ->where('from_date', '>=', Carbon::now())
                ->where('from_date', '<=', Carbon::now()->addDays(7))
                ->get();
            
            $staffLeaveData[] = [
                'id' => $staff->id,
                'name' => $staff->name,
                'casual_quota' => $quota->casual_quota ?? 12,
                'casual_used' => $quota->casual_used ?? 0,
                'casual_remaining' => ($quota->casual_quota ?? 12) - ($quota->casual_used ?? 0),
                'medical_leaves' => $medicalLeaves,
                'pending_leaves' => $pendingLeaves,
                'upcoming_leaves' => $upcomingLeaves,
                'has_warning' => (($quota->casual_quota ?? 12) - ($quota->casual_used ?? 0)) <= 3 || $medicalLeaves >= 5,
            ];
        }
        
        return collect($staffLeaveData);
    }
    
    public static function canView(): bool
    {
        $user = Auth::user();
        $isManager = $user->roles()->where('name', 'like', 'manager%')->exists();
        $isKepala = $user->roles()->where('name', 'like', 'kepala%')->exists();
        
        return $isManager || $isKepala;
    }

    public static function getSort(): int
    {
        return 2; // Ensures this widget is at the top
    }
}