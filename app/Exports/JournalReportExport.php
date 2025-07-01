<?php

namespace App\Exports;

use App\Models\Journal;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting; // Import this
use PhpOffice\PhpSpreadsheet\Style\NumberFormat; // Import this

class JournalReportExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithColumnFormatting
{
    protected $internId;
    protected $month;
    protected $year;

    public function __construct(int $internId, int $month, int $year)
    {
        $this->internId = $internId;
        $this->month = $month;
        $this->year = $year;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query()
    {
        // Query for the specific intern's journals in the given month and year
        // We use with('intern') to eager load the relationship, making it more efficient
        return Journal::query()
            ->with('intern') // Eager load the intern's data
            ->where('intern_id', $this->internId)
            ->whereYear('entry_date', $this->year)
            ->whereMonth('entry_date', $this->month)
            ->orderBy('entry_date', 'asc');
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        // Define the NEW column headers for the Excel file
        return [
            'Nama Magang',
            'Asal Perguruan',
            'Tanggal',
            'Waktu Mulai',
            'Waktu Selesai',
            'Status',
            'Aktivitas',
            'Alasan Ketidakhadiran',
        ];
    }

    /**
     * @param Journal $journal
     * @return array
     */
    public function map($journal): array
    {
        // Map each journal record to a row in the Excel file
        // We can now access the intern's data via the relationship
        return [
            $journal->intern->name,
            $journal->intern->school->name ?? 'N/A', // Use null-coalescing for safety
            $journal->entry_date, // This will be formatted by withColumnFormatting
            $journal->start_time,
            $journal->end_time,
            $journal->status,
            $journal->activity,
            $journal->reason_of_absence,
        ];
    }

    /**
     * @return array
     */
    public function columnFormats(): array
    {
        // This method formats the columns in the Excel file
        // 'C' is the third column ('Tanggal')
        // We format it as Day-Month-Year (e.g., 15-Nov-2023)
        return [
            'C' => NumberFormat::FORMAT_DATE_DDMMYYYY,
        ];
    }
}