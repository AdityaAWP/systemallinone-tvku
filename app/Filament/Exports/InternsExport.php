<?php

namespace App\Filament\Exports;

use App\Exports\InternsByStatusSheetExport;
use App\Models\Intern;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Carbon\Carbon;

class InternsExport implements WithMultipleSheets
{
    protected $institutionType;

    public function __construct($institutionType = 'all')
    {
        $this->institutionType = $institutionType;
    }

    public function sheets(): array
    {
        // 1. Fetch all interns based on the initial filter. This is our master list.
        $query = Intern::with(['school', 'internDivision'])->orderBy('name');

        if ($this->institutionType !== 'all') {
            $query->where('institution_type', $this->institutionType);
        }

        $allInterns = $query->get();
        
        $sheets = [];

        // 2. Add the FIRST sheet: "Data Magang" which includes ALL interns and the status column.
        $sheets[] = new InternsByStatusSheetExport(
            'Data Magang', // Sheet Title
            $allInterns,   // The full collection of interns
            true           // The crucial third parameter: YES, include the status column
        );

        // 3. Group the master list by calculated status to create the other sheets.
        $groupedInterns = $allInterns->groupBy(function ($intern) {
            $now = Carbon::now();
            $start = Carbon::parse($intern->start_date);
            $end = Carbon::parse($intern->end_date);

            if (!$end) {
                return 'Status Tidak Diketahui';
            }
            
            $hampirStart = $end->copy()->subMonth();

            if ($now->isBefore($start)) {
                return 'Akan Datang';
            } elseif ($now->isAfter($end)) {
                return 'Selesai';
            } elseif ($now->isBetween($hampirStart, $end)) {
                return 'Hampir Selesai';
            } else {
                return 'Aktif';
            }
        });
        
        // 4. Create a sheet for each status group, in a specific order.
        $statusOrder = ['Aktif', 'Hampir Selesai', 'Akan Datang', 'Selesai', 'Status Tidak Diketahui'];

        foreach ($statusOrder as $status) {
            if ($groupedInterns->has($status)) {
                // Add the subsequent sheets, WITHOUT the status column
                $sheets[] = new InternsByStatusSheetExport(
                    $status, // The sheet title (e.g., "Aktif")
                    $groupedInterns->get($status),
                    false // NO, do not include the status column on these sheets
                );
            }
        }
        
        return $sheets;
    }
}