<?php

namespace App\Exports;

use App\Exports\Sheets\LeaveMonthlySheet;
use App\Models\Leave;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Illuminate\Database\Eloquent\Builder;

class LeaveYearlyExport implements WithMultipleSheets
{
    use Exportable;

    protected int $year;
    protected ?int $userId;
    protected ?array $divisionIds;

    public function __construct(int $year, ?int $userId = null, ?array $divisionIds = null)
    {
        $this->year = $year;
        $this->userId = $userId;
        $this->divisionIds = $divisionIds;
    }

    public function sheets(): array
    {
        $sheets = [];

        // Selalu buat sheet untuk setiap bulan Januari-Desember
        for ($month = 1; $month <= 12; $month++) {
            $sheets[] = new \App\Exports\Sheets\LeaveMonthlySheet($this->year, $month, $this->userId, $this->divisionIds);
        }

        return $sheets;
    }
}