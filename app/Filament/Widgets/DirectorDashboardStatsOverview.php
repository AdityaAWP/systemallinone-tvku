<?php

namespace App\Filament\Widgets;

use App\Models\Assignment;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DirectorDashboardStatsOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
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

        // Total assignment value this month (only paid type)
        $totalValueThisMonth = Assignment::where('approval_status', Assignment::STATUS_APPROVED)
            ->where('type', Assignment::TYPE_PAID)
            ->whereBetween('approved_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->sum('amount');

        return [
            Stat::make('Pending Assignments', $pendingCount)
                ->description('Awaiting your approval')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Due This Week', $pendingThisWeek)
                ->description('Assignments with upcoming deadlines')
                ->descriptionIcon('heroicon-m-calendar')
                ->color($pendingThisWeek > 0 ? 'danger' : 'success'),

            Stat::make('Approved This Month', $approvedThisMonth)
                ->description('Since ' . Carbon::now()->startOfMonth()->format('d M Y'))
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Monthly Value', 'Rp ' . number_format($totalValueThisMonth, 0, ',', '.'))
                ->description('Total approved amount this month')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
        ];
    }
}