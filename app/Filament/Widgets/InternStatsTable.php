<?php

namespace App\Filament\Widgets;

use App\Models\Intern;
use App\Models\InternDivision;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class InternStatsTable extends Widget
{
    protected static string $view = 'filament.widgets.intern-stats-table';
    protected int|string|array $columnSpan = 'full';
    protected static ?int $sort = 3;
    
    public static function canView(): bool
    {
        $user = Auth::user();
        return $user && $user->hasRole('admin_magang');
    }

    public function getInternData()
    {
        $now = Carbon::now();

        $totalInterns = Intern::count();
        $activeInterns = Intern::where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->count();

        $schoolTypeCounts = DB::table('intern_schools')
            ->join('interns', 'intern_schools.id', '=', 'interns.school_id')
            ->select('intern_schools.type', DB::raw('count(*) as total'))
            ->groupBy('intern_schools.type')
            ->pluck('total', 'intern_schools.type')
            ->toArray();

        $divisionCounts = DB::table('intern_divisions')
            ->join('interns', 'intern_divisions.id', '=', 'interns.intern_division_id')
            ->select('intern_divisions.name', DB::raw('count(*) as total'))
            ->groupBy('intern_divisions.name')
            ->pluck('total', 'intern_divisions.name')
            ->toArray();

        // Get all divisions from database instead of hardcoded array
        $allDivisions = InternDivision::pluck('name')->toArray();

        return [
            ['label' => 'Total Anak Magang', 'value' => $totalInterns],
            ['label' => 'Anak Magang Aktif', 'value' => $activeInterns],
            ['label' => 'Total Perguruan Tinggi', 'value' => $schoolTypeCounts['Perguruan Tinggi'] ?? 0],
            ['label' => 'Total SMA/SMK', 'value' => $schoolTypeCounts['SMA/SMK'] ?? 0],
            ...collect($allDivisions)->map(fn($div) => [
                'label' => "Divisi $div",
                'value' => $divisionCounts[$div] ?? 0,
            ])->toArray()
        ];
    }

    protected function getViewData(): array
    {
        return [
            'internData' => $this->getInternData(),
        ];
    }
}
