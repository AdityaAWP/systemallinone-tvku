<?php

namespace App\Filament\Widgets;

use App\Models\Leave;
use App\Models\DailyReport;
use App\Models\Overtime;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class HRDDashboardStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        $user = Auth::user();
        return $user && $user->hasRole('hrd');
    }

    public static function getSort(): int
    {
        return 2;
    }

    protected function getStats(): array
    {
        // Jumlah pengajuan cuti (pending)
        $pendingLeave = Leave::where('status', 'pending')->count();
        // Jumlah pengajuan lembur (semua data)
        $totalOvertime = Overtime::count();
        // Jumlah laporan harian yang masuk bulan ini
        $currentMonth = now()->month;
        $currentYear = now()->year;
        $dailyReports = DailyReport::whereMonth('entry_date', $currentMonth)
            ->whereYear('entry_date', $currentYear)
            ->count();

        return [
            Stat::make('Pengajuan Cuti Pending', $pendingLeave)
                ->description('Jumlah cuti yang menunggu persetujuan')
                ->icon('heroicon-o-clipboard-document-list')
                ->color('warning'),
            Stat::make('Jumlah Pengajuan Lembur', $totalOvertime)
                ->description('Total semua pengajuan lembur')
                ->icon('heroicon-o-clock')
                ->color('info'),
            Stat::make('Laporan Harian Bulan Ini', $dailyReports)
                ->description('Total laporan harian yang masuk bulan ini')
                ->icon('heroicon-o-calendar-days')
                ->color('info'),
        ];
    }
}
