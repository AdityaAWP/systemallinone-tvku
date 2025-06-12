<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class MonthlyLeaveSummaryWidget extends BaseWidget
{
    protected static ?string $heading = 'Rekap Pengajuan Cuti Pegawai Bulanan';
    protected static ?int $sort = 2;
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = Auth::user();
        $myLeave = request()->input('tableFilters.my_leave.value');
        
        // Hanya tampil di halaman cuti (LeaveResource) untuk HRD
        $currentRoute = request()->route()?->getName();
        $isLeaveRoute = str_contains($currentRoute ?? '', 'filament.admin.resources.leaves.index');
        
        if (!$isLeaveRoute || !$user->hasRole('hrd')) {
            return false;
        }
        
        // Untuk HRD: tampil secara default (tanpa filter) dan ketika filter "Semua Cuti Staff" (false)
        // Tidak tampil ketika filter "Cuti Saya" (true)
        return $myLeave !== 'true';
    }

    /**
     * Menghitung hari kerja (tidak termasuk weekend dan hari libur)
     */
    private function calculateWorkingDays($fromDate, $toDate): int
    {
        $from = Carbon::parse($fromDate);
        $to = Carbon::parse($toDate);

        // Daftar hari libur nasional Indonesia 2025 (bisa disesuaikan atau diambil dari database)
        $holidays = [
            '2025-01-01', // Tahun Baru
            '2025-01-29', // Tahun Baru Imlek
            '2025-02-12', // Isra Mi'raj
            '2025-03-14', // Hari Suci Nyepi
            '2025-03-29', // Wafat Isa Al-Masih
            '2025-03-30', // Idul Fitri
            '2025-03-31', // Idul Fitri
            '2025-04-01', // Cuti Bersama Idul Fitri
            '2025-05-01', // Hari Buruh
            '2025-05-12', // Hari Raya Waisak
            '2025-05-29', // Kenaikan Isa Al-Masih
            '2025-06-01', // Hari Lahir Pancasila
            '2025-06-06', // Idul Adha
            '2025-06-27', // Tahun Baru Islam
            '2025-08-17', // Hari Kemerdekaan RI
            '2025-09-05', // Maulid Nabi Muhammad SAW
            '2025-12-25', // Hari Raya Natal
        ];

        $workingDays = 0;
        $current = $from->copy();

        while ($current->lte($to)) {
            // Skip weekend (Sabtu = 6, Minggu = 0)
            if (!$current->isWeekend()) {
                // Skip hari libur nasional
                if (!in_array($current->format('Y-m-d'), $holidays)) {
                    $workingDays++;
                }
            }
            $current->addDay();
        }

        return $workingDays;
    }

    /**
     * Menghitung total hari kerja cuti untuk bulan tertentu
     */
    private function getMonthlyWorkingDays(User $record, int $month, int $year): int
    {
        $leaves = $record->leaves()
            ->whereMonth('from_date', $month)
            ->whereYear('from_date', $year)
            ->where('status', 'approved')
            ->get();

        $totalWorkingDays = 0;
        
        foreach ($leaves as $leave) {
            $workingDays = $this->calculateWorkingDays($leave->from_date, $leave->to_date);
            $totalWorkingDays += $workingDays;
        }

        return $totalWorkingDays;
    }

    /**
     * Menghitung total hari kerja cuti untuk seluruh tahun
     */
    private function getYearlyWorkingDays(User $record, int $year): int
    {
        $leaves = $record->leaves()
            ->whereYear('from_date', $year)
            ->where('status', 'approved')
            ->get();

        $totalWorkingDays = 0;
        
        foreach ($leaves as $leave) {
            $workingDays = $this->calculateWorkingDays($leave->from_date, $leave->to_date);
            $totalWorkingDays += $workingDays;
        }

        return $totalWorkingDays;
    }

    public function table(Table $table): Table
    {
        $currentYear = Carbon::now()->year;

        return $table
            ->query(
                User::query()
                    ->whereHas('leaves', function($query) use ($currentYear) {
                        $query->whereYear('from_date', $currentYear)
                              ->where('status', 'approved');
                    })
                    ->orderBy('name')
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('No')
                    ->rowIndex(),
                    
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Lengkap')
                    ->searchable(),
                
                // January
                Tables\Columns\TextColumn::make('jan_leaves')
                    ->label('Jan')
                    ->getStateUsing(function (User $record) use ($currentYear) {
                        $workingDays = $this->getMonthlyWorkingDays($record, 1, $currentYear);
                        return $workingDays > 0 ? $workingDays : '-';
                    })
                    ->alignCenter(),
                
                // February
                Tables\Columns\TextColumn::make('feb_leaves')
                    ->label('Feb')
                    ->getStateUsing(function (User $record) use ($currentYear) {
                        $workingDays = $this->getMonthlyWorkingDays($record, 2, $currentYear);
                        return $workingDays > 0 ? $workingDays : '-';
                    })
                    ->alignCenter(),
                
                // March
                Tables\Columns\TextColumn::make('mar_leaves')
                    ->label('Mar')
                    ->getStateUsing(function (User $record) use ($currentYear) {
                        $workingDays = $this->getMonthlyWorkingDays($record, 3, $currentYear);
                        return $workingDays > 0 ? $workingDays : '-';
                    })
                    ->alignCenter(),
                
                // April
                Tables\Columns\TextColumn::make('apr_leaves')
                    ->label('Apr')
                    ->getStateUsing(function (User $record) use ($currentYear) {
                        $workingDays = $this->getMonthlyWorkingDays($record, 4, $currentYear);
                        return $workingDays > 0 ? $workingDays : '-';
                    })
                    ->alignCenter(),
                
                // May
                Tables\Columns\TextColumn::make('may_leaves')
                    ->label('Mei')
                    ->getStateUsing(function (User $record) use ($currentYear) {
                        $workingDays = $this->getMonthlyWorkingDays($record, 5, $currentYear);
                        return $workingDays > 0 ? $workingDays : '-';
                    })
                    ->alignCenter(),
                
                // June
                Tables\Columns\TextColumn::make('jun_leaves')
                    ->label('Jun')
                    ->getStateUsing(function (User $record) use ($currentYear) {
                        $workingDays = $this->getMonthlyWorkingDays($record, 6, $currentYear);
                        return $workingDays > 0 ? $workingDays : '-';
                    })
                    ->alignCenter(),
                
                // July
                Tables\Columns\TextColumn::make('jul_leaves')
                    ->label('Jul')
                    ->getStateUsing(function (User $record) use ($currentYear) {
                        $workingDays = $this->getMonthlyWorkingDays($record, 7, $currentYear);
                        return $workingDays > 0 ? $workingDays : '-';
                    })
                    ->alignCenter(),
                
                // August
                Tables\Columns\TextColumn::make('aug_leaves')
                    ->label('Agu')
                    ->getStateUsing(function (User $record) use ($currentYear) {
                        $workingDays = $this->getMonthlyWorkingDays($record, 8, $currentYear);
                        return $workingDays > 0 ? $workingDays : '-';
                    })
                    ->alignCenter(),
                
                // September
                Tables\Columns\TextColumn::make('sep_leaves')
                    ->label('Sep')
                    ->getStateUsing(function (User $record) use ($currentYear) {
                        $workingDays = $this->getMonthlyWorkingDays($record, 9, $currentYear);
                        return $workingDays > 0 ? $workingDays : '-';
                    })
                    ->alignCenter(),
                
                // October
                Tables\Columns\TextColumn::make('oct_leaves')
                    ->label('Okt')
                    ->getStateUsing(function (User $record) use ($currentYear) {
                        $workingDays = $this->getMonthlyWorkingDays($record, 10, $currentYear);
                        return $workingDays > 0 ? $workingDays : '-';
                    })
                    ->alignCenter(),
                
                // November
                Tables\Columns\TextColumn::make('nov_leaves')
                    ->label('Nov')
                    ->getStateUsing(function (User $record) use ($currentYear) {
                        $workingDays = $this->getMonthlyWorkingDays($record, 11, $currentYear);
                        return $workingDays > 0 ? $workingDays : '-';
                    })
                    ->alignCenter(),
                
                // December
                Tables\Columns\TextColumn::make('dec_leaves')
                    ->label('Des')
                    ->getStateUsing(function (User $record) use ($currentYear) {
                        $workingDays = $this->getMonthlyWorkingDays($record, 12, $currentYear);
                        return $workingDays > 0 ? $workingDays : '-';
                    })
                    ->alignCenter(),
                
                // Total
                Tables\Columns\TextColumn::make('total_leaves')
                    ->label('Total')
                    ->getStateUsing(function (User $record) use ($currentYear) {
                        $totalWorkingDays = $this->getYearlyWorkingDays($record, $currentYear);
                        return $totalWorkingDays > 0 ? $totalWorkingDays : '-';
                    })
                    ->formatStateUsing(fn ($state) => $state === '-' ? $state : $state . ' Hari')
                    ->alignCenter()
                    ->weight('bold'),
            ])
            ->paginated([10, 25, 50, 'all'])
            ->defaultPaginationPageOption(10);
    }
}