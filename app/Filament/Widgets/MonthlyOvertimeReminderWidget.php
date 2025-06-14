<?php

namespace App\Filament\Widgets;

use App\Models\Overtime;
use App\Models\User;
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
        return 1; // High priority for reminder
    }

    /**
     * Untuk debugging - hapus method ini setelah testing
     */
   
}