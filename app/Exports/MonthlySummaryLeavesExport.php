<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Carbon;

class MonthlySummaryLeavesExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles
{
    protected int $year;

    public function __construct(int $year)
    {
        $this->year = $year;
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

    public function collection()
    {
        return User::query()
            ->whereHas('leaves', function($query) {
                $query->whereYear('from_date', $this->year)
                      ->where('status', 'approved');
            })
            ->orderBy('name')
            ->get();
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama Lengkap',
            'Jan',
            'Feb',
            'Mar',
            'Apr',
            'Mei',
            'Jun',
            'Jul',
            'Agu',
            'Sep',
            'Okt',
            'Nov',
            'Des',
            'Total'
        ];
    }

    public function map($user): array
    {
        static $no = 1;
        
        $monthlyData = [];
        for ($month = 1; $month <= 12; $month++) {
            $workingDays = $this->getMonthlyWorkingDays($user, $month, $this->year);
            $monthlyData[] = $workingDays > 0 ? $workingDays : '-';
        }

        $totalWorkingDays = $this->getYearlyWorkingDays($user, $this->year);
        $total = $totalWorkingDays > 0 ? $totalWorkingDays . ' Hari' : '-';

        return array_merge(
            [$no++, $user->name],
            $monthlyData,
            [$total]
        );
    }

    public function title(): string
    {
        return "Rekap Cuti Bulanan {$this->year}";
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold
            1 => ['font' => ['bold' => true]],
            
            // Style the total column as bold
            'O:O' => ['font' => ['bold' => true]],
        ];
    }
}
