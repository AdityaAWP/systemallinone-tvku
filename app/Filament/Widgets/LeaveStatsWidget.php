<?php

namespace App\Filament\Widgets;

use App\Models\Leave;
use App\Models\LeaveQuota;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class LeaveStatsWidget extends BaseWidget
{


    protected static ?string $title = 'Leave Statistics';
    protected static ?int $sort = 1;
    use HasWidgetShield;


    public function getStats(): array
    {
        $user = Auth::user();
        
        if (!$user->hasRole('staff')) {
            return [];
        }
        
        $quota = LeaveQuota::getUserQuota($user->id);
        $currentYear = date('Y');
        
        // Get medical leaves count
        $medicalLeaves = Leave::where('user_id', $user->id)
            ->where('leave_type', 'medical')
            ->whereYear('from_date', $currentYear)
            ->where('status', 'approved')
            ->count();
        
        // Get pending leaves count
        $pendingLeaves = Leave::where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();
        
        return [
            Stat::make('Casual Leave', $quota->remaining_casual_quota . ' days')
                ->description($quota->casual_used . ' days used this year')
                ->descriptionIcon('heroicon-o-calendar')
                ->color('success'),
                
            Stat::make('Medical Leave', $medicalLeaves . ' days')
                ->description('Used this year')
                ->descriptionIcon('heroicon-o-heart')
                ->color('warning'),
                
            Stat::make('Pending Requests', $pendingLeaves)
                ->description('Awaiting approval')
                ->descriptionIcon('heroicon-o-clock')
                ->color($pendingLeaves > 0 ? 'warning' : 'success'),
        ];
    }

    public static function canView(): bool
    {
        // Keep your existing visibility logic
        $currentRoute = request()->route()?->getName();
        return $currentRoute && str_contains($currentRoute, 'leave');
    }

    public static function getSort(): int
    {
        return 1; // Ensures this widget is at the top
    }
    
}