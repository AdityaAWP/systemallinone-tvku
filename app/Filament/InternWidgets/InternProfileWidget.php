<?php

namespace App\Filament\InternWidgets;

use App\Models\Intern;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class InternProfileWidget extends Widget
{
    protected static string $view = 'filament.intern-widgets.intern-profile-widget';
    protected int | string | array $columnSpan = 1;
    protected static ?int $sort = 2; 
    public ?Intern $intern = null;
    
    public function mount(): void
    {
        /** @var Intern|null $intern */
        $intern = Auth::guard('intern')->user();
        $this->intern = $intern;
    }
    
    protected function getViewData(): array
    {
        if (!$this->intern) {
            return ['intern' => null];
        }

        $startDate = Carbon::parse($this->intern->start_date);
        $endDate = Carbon::parse($this->intern->end_date);
        $now = Carbon::now();
        
        // Calculate duration and remaining days
        $totalDurationDays = $startDate->diffInDays($endDate);
        $daysElapsed = $startDate->diffInDays($now);
        $daysRemaining = $now->diffInDays($endDate);
        
        // Calculate progress percentage
        $progressPercentage = $totalDurationDays > 0 ? min(100, ($daysElapsed / $totalDurationDays) * 100) : 0;
        
        // Determine status
        $status = $this->getInternshipStatus($startDate, $endDate, $now);
        
        return [
            'intern' => $this->intern,
            'total_duration_days' => $totalDurationDays,
            'days_elapsed' => $daysElapsed,
            'days_remaining' => max(0, $daysRemaining),
            'progress_percentage' => round($progressPercentage, 1),
            'status' => $status,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
    }
    
    private function getInternshipStatus(Carbon $startDate, Carbon $endDate, Carbon $now): array
    {
        $hampirStart = $endDate->copy()->subMonth();
        
        if ($now->lessThan($startDate)) {
            return [
                'label' => 'Akan Datang',
                'color' => 'warning',
                'icon' => 'heroicon-o-clock',
                'description' => 'Magang akan dimulai pada ' . $startDate->translatedFormat('d F Y')
            ];
        } elseif ($now->greaterThanOrEqualTo($hampirStart) && $now->lessThanOrEqualTo($endDate)) {
            return [
                'label' => 'Hampir Selesai',
                'color' => 'danger',
                'icon' => 'heroicon-o-exclamation-triangle',
                'description' => 'Magang akan berakhir dalam ' . $now->diffInDays($endDate) . ' hari'
            ];
        } elseif ($now->between($startDate, $hampirStart->subSecond())) {
            return [
                'label' => 'Aktif',
                'color' => 'success',
                'icon' => 'heroicon-o-check-circle',
                'description' => 'Magang sedang berlangsung'
            ];
        } else {
            return [
                'label' => 'Selesai',
                'color' => 'gray',
                'icon' => 'heroicon-o-flag',
                'description' => 'Magang telah selesai pada ' . $endDate->translatedFormat('d F Y')
            ];
        }
    }
    
    public static function canView(): bool
    {
        return Auth::guard('intern')->check();
    }
}