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


    protected static ?string $title = 'Statistik Cuti';
    protected static ?int $sort = 1;
    use HasWidgetShield;


    public function getStats(): array
    {
        $user = Auth::user();
        
        $quota = LeaveQuota::getUserQuota($user->id);
        $currentYear = date('Y');
        
        // Ambil jumlah cuti sakit
        $medicalLeaves = Leave::where('user_id', $user->id)
            ->where('leave_type', 'medical')
            ->whereYear('from_date', $currentYear)
            ->where('status', 'approved')
            ->count();
        
        // Ambil jumlah cuti yang masih menunggu persetujuan
        $pendingLeaves = Leave::where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();
        
        return [
            Stat::make('Cuti Tahunan', $quota->remaining_casual_quota . ' kesempatan')
                ->description($quota->casual_used . ' kali sudah diambil tahun ini')
                ->descriptionIcon('heroicon-o-calendar')
                ->color('success'),
                
            Stat::make('Cuti Sakit', $medicalLeaves . ' Kesempatan')
                ->description('Terpakai tahun ini')
                ->descriptionIcon('heroicon-o-heart')
                ->color('warning'),
                
            Stat::make('Permintaan Menunggu', $pendingLeaves)
                ->description('Menunggu persetujuan')
                ->descriptionIcon('heroicon-o-clock')
                ->color($pendingLeaves > 0 ? 'warning' : 'success'),
        ];
    }

    public static function canView(): bool
    {
        // Tetap gunakan logika visibilitas yang ada
        $currentRoute = request()->route()?->getName();
        return $currentRoute && str_contains($currentRoute, 'leave');
    }

    public static function getSort(): int
    {
        return 1; // Memastikan widget ini berada di atas
    }
    
}