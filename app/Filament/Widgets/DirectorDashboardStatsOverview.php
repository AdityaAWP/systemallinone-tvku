<?php

namespace App\Filament\Widgets;

use App\Models\Assignment;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class DirectorDashboardStatsOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    public static function canView(): bool
    {
        $user = Auth::user();
        return $user->hasAnyRole(['direktur_keuangan', 'admin_keuangan']) && !$user->hasRole('staff_keuangan');
    }

    protected function getStats(): array
    {
        $user = Auth::user();
        
        if (!$user->hasAnyRole(['direktur_keuangan', 'admin_keuangan']) || $user->hasRole('staff_keuangan')) {
            return [];
        }

        // Total semua surat
        $totalAssignments = Assignment::where('type', Assignment::TYPE_PAID)->count();

        // Total pending assignments
        $pendingCount = Assignment::where('approval_status', Assignment::STATUS_PENDING)
            ->where('type', Assignment::TYPE_PAID)
            ->count();

        // Pending assignments with deadline this week
        $pendingThisWeek = Assignment::where('approval_status', Assignment::STATUS_PENDING)
            ->where('type', Assignment::TYPE_PAID)
            ->whereBetween('deadline', [Carbon::now(), Carbon::now()->endOfWeek()])
            ->count();

        // Approved assignments this month
        $approvedThisMonth = Assignment::where('approval_status', Assignment::STATUS_APPROVED)
            ->whereBetween('approved_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->count();

        return [
            Stat::make('Total Surat', $totalAssignments)
                ->description('Total semua surat')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),
                
            Stat::make('Pending Assignments', $pendingCount)
                ->description('Menunggu persetujuan')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Due This Week', $pendingThisWeek)
                ->description('Batas waktu minggu ini')
                ->descriptionIcon('heroicon-m-calendar')
                ->color($pendingThisWeek > 0 ? 'danger' : 'success'),

            Stat::make('Approved This Month', $approvedThisMonth)
                ->description('Disetujui bulan ini')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
        ];
    }
}