<?php

namespace App\Exports\Sheets;

use App\Models\DailyReport;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Illuminate\Support\Carbon;

class DailyReportMonthlySheet implements FromQuery, WithTitle, WithHeadings, WithMapping
{
    private int $year;
    private int $month;

    public function __construct(int $year, int $month)
    {
        $this->year = $year;
        $this->month = $month;
    }

    /**
     * @return 
     */
    public function query()
    {
        return DailyReport::query()
            ->with('user') // Eager load user relationship
            ->whereYear('entry_date', $this->year)
            ->whereMonth('entry_date', $this->month)
            ->orderBy('entry_date'); // Order by date within the month
    }

    /**
     * @return string
     */
    public function title(): string
    {
        // Use Carbon to get month name
        return Carbon::create()->month($this->month)->format('F');
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Nama',
            'Tanggal',
            'Check In',
            'Check Out',
            'Jam Kerja',
            'Keterangan',
        ];
    }

    /**
     * @param DailyReport $report
     * @return array
     */
    public function map($report): array
    {
        return [
            $report->user?->name ?? 'N/A', // Handle potential null user
            $report->entry_date,
            $report->check_in,
            $report->check_out,
            $report->work_hours,
            strip_tags((string) $report->description), // Ensure description is string and strip tags
        ];
    }
}

