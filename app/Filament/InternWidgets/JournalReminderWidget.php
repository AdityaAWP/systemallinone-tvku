<?php

namespace App\Filament\InternWidgets;

use App\Models\Journal;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class JournalReminderWidget extends Widget
{
    protected static string $view = 'filament.intern-widgets.journal-reminder-widget';
    protected int | string | array $columnSpan = 1;
    protected static ?int $sort = 1; // Set after intern profile widget

    public function getJournalReminderData()
    {
        $intern = Auth::guard('intern')->user();
        
        if (!$intern) {
            return null;
        }

        $today = Carbon::today();
        $currentWeek = Carbon::now()->startOfWeek();
        $currentMonth = Carbon::now()->startOfMonth();
        
        // Check if journal entry exists for today
        $todayJournal = Journal::where('intern_id', $intern->id)
            ->whereDate('entry_date', $today)
            ->first();        // Get journal entries for this week
        $weekJournals = Journal::where('intern_id', $intern->id)
            ->whereBetween('entry_date', [$currentWeek->toDateString(), $currentWeek->copy()->endOfWeek()->toDateString()])
            ->orderBy('entry_date', 'desc')
            ->get();        // Get journal entries for this month
        $monthJournals = Journal::where('intern_id', $intern->id)
            ->whereBetween('entry_date', [$currentMonth->toDateString(), $currentMonth->copy()->endOfMonth()->toDateString()])
            ->get();        // Calculate statistics
        $weekdaysThisWeek = $this->getWeekdaysInRange($currentWeek, $currentWeek->copy()->endOfWeek());
        $weekdaysThisMonth = $this->getWeekdaysInRange($currentMonth, $currentMonth->copy()->endOfMonth());
        
        // Create arrays of weekday date strings for efficient lookup
        $weekdayStringsWeek = $weekdaysThisWeek->map(fn($date) => $date->toDateString())->toArray();
        $weekdayStringsMonth = $weekdaysThisMonth->map(fn($date) => $date->toDateString())->toArray();
        
        // Count completed journals for weekdays only
        $completedThisWeek = $weekJournals->filter(function($journal) use ($weekdayStringsWeek) {
            return in_array($journal->entry_date->toDateString(), $weekdayStringsWeek);
        })->count();
        
        $completedThisMonth = $monthJournals->filter(function($journal) use ($weekdayStringsMonth) {
            return in_array($journal->entry_date->toDateString(), $weekdayStringsMonth);
        })->count();

        // Get recent journal entries
        $recentJournals = Journal::where('intern_id', $intern->id)
            ->orderBy('entry_date', 'desc')
            ->limit(3) // Reduced for cleaner display
            ->get();

        // Check if should show reminder
        $shouldShowReminder = $this->shouldShowReminder($today, $todayJournal);

        return [
            'intern' => $intern,
            'today' => $today,
            'today_journal' => $todayJournal,
            'week_journals' => $weekJournals,
            'month_journals' => $monthJournals,
            'completed_this_week' => $completedThisWeek,
            'total_weekdays_week' => $weekdaysThisWeek->count(),
            'completed_this_month' => $completedThisMonth,
            'total_weekdays_month' => $weekdaysThisMonth->count(),
            'recent_journals' => $recentJournals,
            'should_show_reminder' => $shouldShowReminder,
            'reminder_message' => $this->getReminderMessage($today, $todayJournal),
        ];
    }

    private function getWeekdaysInRange(Carbon $start, Carbon $end)
    {
        $weekdays = collect();
        $current = $start->copy();
        
        while ($current->lte($end)) {
            // Only include weekdays (Monday to Friday)
            if ($current->isWeekday()) {
                $weekdays->push($current->copy());
            }
            $current->addDay();
        }
        
        return $weekdays;
    }

    private function shouldShowReminder(Carbon $today, $todayJournal): bool
    {
        // Don't show reminder on weekends
        if ($today->isWeekend()) {
            return false;
        }

        // Show reminder if no journal entry for today
        if (!$todayJournal) {
            return true;
        }

        return false;
    }

    private function getReminderMessage(Carbon $today, $todayJournal): string
    {
        if ($today->isWeekend()) {
            return 'Selamat menikmati akhir pekan! Journal akan tersedia lagi pada hari kerja.';
        }

        if (!$todayJournal) {
            $dayName = $today->translatedFormat('l');
            return "Jangan lupa mengisi journal untuk hari {$dayName}, " . $today->translatedFormat('d F Y') . "!";
        }

        return 'Journal hari ini sudah terisi. Terima kasih atas kedisiplinan Anda!';
    }

    public static function canView(): bool
    {
        return Auth::guard('intern')->check();
    }
}