<?php

namespace App\Filament\Widgets;

use App\Models\Overtime;
use App\Models\User;
use App\Models\DailyReport;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class MonthlyOvertimeReminderWidget extends Widget
{
    protected static string $view = 'filament.widgets.monthly-overtime-reminder-widget';
    
    protected int | string | array $columnSpan = 'full';

    public function getOvertimeReminderData()
    {
        $user = Auth::user();
        $currentDate = Carbon::now();
        $currentMonth = $currentDate->month;
        $currentYear = $currentDate->year;
        
        // Get previous month's overtime data
        $previousMonth = $currentDate->copy()->subMonth();
        $previousMonthOvertimes = Overtime::where('user_id', $user->id)
            ->whereYear('tanggal_overtime', $previousMonth->year)
            ->whereMonth('tanggal_overtime', $previousMonth->month)
            ->get();
        
        // Get current month's overtime data
        $currentMonthOvertimes = Overtime::where('user_id', $user->id)
            ->whereYear('tanggal_overtime', $currentYear)
            ->whereMonth('tanggal_overtime', $currentMonth)
            ->get();
        
        // Calculate statistics
        $previousMonthTotal = $previousMonthOvertimes->sum(function($overtime) {
            return $overtime->overtime_hours + ($overtime->overtime_minutes / 60);
        });
        
        $currentMonthTotal = $currentMonthOvertimes->sum(function($overtime) {
            return $overtime->overtime_hours + ($overtime->overtime_minutes / 60);
        });
        
        $previousMonthCount = $previousMonthOvertimes->count();
        $currentMonthCount = $currentMonthOvertimes->count();
        
        // Get recent overtime entries (last 5)
        $recentOvertimes = Overtime::where('user_id', $user->id)
            ->orderBy('tanggal_overtime', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        
        // Check if user has submitted overtime this month
        $hasSubmittedThisMonth = $currentMonthCount > 0;
        
        // Daily Report Data
        $dailyReportData = $this->getDailyReportData($user, $currentDate);
        
        // Logic untuk menentukan kapan reminder harus ditampilkan
        $shouldShowReminder = $this->shouldShowReminder($currentDate, $hasSubmittedThisMonth, $previousMonthCount);
        
        return [
            'current_date' => $currentDate,
            'current_month_name' => $currentDate->translatedFormat('F Y'), // Menggunakan translatedFormat untuk bahasa Indonesia
            'previous_month_name' => $previousMonth->translatedFormat('F Y'),
            'previous_month_total' => round($previousMonthTotal, 1),
            'previous_month_count' => $previousMonthCount,
            'current_month_total' => round($currentMonthTotal, 1),
            'current_month_count' => $currentMonthCount,
            'has_submitted_this_month' => $hasSubmittedThisMonth,
            'recent_overtimes' => $recentOvertimes,
            'should_show_reminder' => $shouldShowReminder,
            'reminder_reason' => $this->getReminderReason($currentDate, $hasSubmittedThisMonth, $previousMonthCount),
            'daily_report' => $dailyReportData,
        ];
    }

    /**
     * Logika untuk menentukan kapan reminder harus ditampilkan
     */
    private function shouldShowReminder($currentDate, $hasSubmittedThisMonth, $previousMonthCount): bool
    {
        // Jangan tampilkan reminder jika sudah mengajukan lembur bulan ini
        if ($hasSubmittedThisMonth) {
            return false;
        }

        // Tampilkan reminder dalam beberapa kondisi:
        
        // 1. Jika di awal bulan (hari 1-10) dan pernah lembur bulan lalu
        if ($currentDate->day <= 10 && $previousMonthCount > 0) {
            return true;
        }
        
        // 2. Jika di pertengahan bulan (hari 11-20) dan belum mengajukan
        if ($currentDate->day >= 11 && $currentDate->day <= 20) {
            return true;
        }
        
        // 3. Jika sudah mendekati akhir bulan (hari 21-31) dan belum mengajukan
        if ($currentDate->day >= 21) {
            return true;
        }
        
        return false;
    }

    /**
     * Mendapatkan alasan kenapa reminder ditampilkan
     */
    private function getReminderReason($currentDate, $hasSubmittedThisMonth, $previousMonthCount): string
    {
        if ($hasSubmittedThisMonth) {
            return '';
        }

        if ($currentDate->day <= 10 && $previousMonthCount > 0) {
            return 'Awal bulan adalah waktu yang tepat untuk mengajukan lembur bulan lalu';
        }
        
        if ($currentDate->day >= 11 && $currentDate->day <= 20) {
            return 'Jangan lupa mengajukan lembur Anda sebelum terlambat';
        }
        
        if ($currentDate->day >= 21) {
            return 'Segera ajukan lembur Anda sebelum bulan berakhir';
        }
        
        return 'Saatnya mengajukan lembur bulanan Anda';
    }

    /**
     * Mendapatkan data laporan harian untuk reminder
     */
    private function getDailyReportData($user, $currentDate)
    {
        $today = $currentDate->toDateString();
        $yesterday = $currentDate->copy()->subDay()->toDateString();
        $currentWeekStart = $currentDate->copy()->startOfWeek();
        $currentWeekEnd = $currentDate->copy()->endOfWeek();
        
        // Check if user has submitted today's report
        $todayReport = DailyReport::where('user_id', $user->id)
            ->where('entry_date', $today)
            ->first();
        
        // Check if user has submitted yesterday's report (only on weekdays)
        $yesterdayReport = null;
        $yesterdayDate = $currentDate->copy()->subDay();
        if ($yesterdayDate->isWeekday()) {
            $yesterdayReport = DailyReport::where('user_id', $user->id)
                ->where('entry_date', $yesterday)
                ->first();
        }
        
        // Get this week's reports count
        $thisWeekReportsCount = DailyReport::where('user_id', $user->id)
            ->whereBetween('entry_date', [$currentWeekStart->toDateString(), $currentWeekEnd->toDateString()])
            ->count();
        
        // Get recent reports (last 5)
        $recentReports = DailyReport::where('user_id', $user->id)
            ->orderBy('entry_date', 'desc')
            ->limit(5)
            ->get();
        
        // Check missing reports in current week (weekdays only)
        $missingDaysCount = 0;
        $currentWeekDays = [];
        for ($date = $currentWeekStart->copy(); $date->lte($currentWeekEnd) && $date->lte($currentDate); $date->addDay()) {
            if ($date->isWeekday()) {
                $currentWeekDays[] = $date->toDateString();
                $hasReport = DailyReport::where('user_id', $user->id)
                    ->where('entry_date', $date->toDateString())
                    ->exists();
                if (!$hasReport) {
                    $missingDaysCount++;
                }
            }
        }
        
        return [
            'has_today_report' => !is_null($todayReport),
            'has_yesterday_report' => !is_null($yesterdayReport),
            'yesterday_is_weekday' => $yesterdayDate->isWeekday(),
            'this_week_reports_count' => $thisWeekReportsCount,
            'missing_days_count' => $missingDaysCount,
            'recent_reports' => $recentReports,
            'should_show_daily_reminder' => $this->shouldShowDailyReportReminder($currentDate, $todayReport, $yesterdayReport, $missingDaysCount),
            'daily_reminder_reason' => $this->getDailyReminderReason($currentDate, $todayReport, $yesterdayReport, $missingDaysCount),
        ];
    }    /**
     * Logika untuk menentukan kapan daily report reminder harus ditampilkan
     */
    private function shouldShowDailyReportReminder($currentDate, $todayReport, $yesterdayReport, $missingDaysCount): bool
    {
        // Jangan tampilkan di weekend
        if ($currentDate->isWeekend()) {
            return false;
        }
        
        // Jangan tampilkan jika sudah buat laporan hari ini
        if ($todayReport) {
            return false;
        }
        
        // Tampilkan jika belum buat laporan hari ini
        if (!$todayReport) {
            return true;
        }
        
        // Tampilkan jika kemarin hari kerja dan belum buat laporan kemarin
        $yesterday = $currentDate->copy()->subDay();
        if ($yesterday->isWeekday() && !$yesterdayReport) {
            return true;
        }
        
        // Tampilkan jika ada hari yang terlewat minggu ini
        if ($missingDaysCount > 0) {
            return true;
        }
        
        return false;
    }    /**
     * Mendapatkan alasan kenapa daily report reminder ditampilkan
     */
    private function getDailyReminderReason($currentDate, $todayReport, $yesterdayReport, $missingDaysCount): string
    {
        if ($currentDate->isWeekend()) {
            return '';
        }
        
        if ($todayReport) {
            return '';
        }
        
        if (!$todayReport) {
            return 'Jangan lupa buat laporan harian untuk hari ini';
        }
        
        $yesterday = $currentDate->copy()->subDay();
        if ($yesterday->isWeekday() && !$yesterdayReport) {
            return 'Anda belum membuat laporan harian untuk kemarin (' . $yesterday->translatedFormat('d F Y') . ')';
        }
        
        if ($missingDaysCount > 0) {
            return "Ada {$missingDaysCount} hari kerja minggu ini yang belum diisi laporannya";
        }
        
        return 'Pastikan laporan harian Anda selalu up to date';
    }

    /**
     * Check if user has any staff role
     */
    private static function isStaff($user): bool
    {
        // Perbaikan: pastikan user memiliki relasi roles
        if (!$user || !method_exists($user, 'roles')) {
            return false;
        }
        
        return $user->roles()->where('name', 'like', 'staff_%')->exists();
    }
    
    public static function canView(): bool
    {
        $user = Auth::user();
        
        // Show only for users with staff roles
        return $user && static::isStaff($user);
    }

    public static function getSort(): int
    {
        return 2; // High priority for reminder
    }
}