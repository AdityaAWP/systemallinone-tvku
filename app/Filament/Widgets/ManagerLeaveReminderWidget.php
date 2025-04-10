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
        
        if (!$manager->hasRole('manager')) {
            return collect();
        }
        
        // Get staff in the same division as the manager
        $staffIds = User::where('division_id', $manager->division_id)
            ->pluck('id')
            ->toArray();
        
        $currentYear = Carbon::now()->year;
        
        $staffLeaveData = [];
        
        foreach ($staffIds as $staffId) {
            $staff = User::find($staffId);
            if (!$staff || $staff->id === $manager->id) continue;
            
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
        return Auth::user()->hasRole('manager');
    }

    public static function getSort(): int
    {
        return 2; // Ensures this widget is at the top
    }
}