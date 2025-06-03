<?php

namespace App\Exports;

use App\Exports\Sheets\DailyReportMonthlySheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class DailyReportExcel implements WithMultipleSheets
{
    use Exportable;

    protected $year;
    protected $userId;

    public function __construct(int $year, int $userId)
    {
        $this->year = $year;
        $this->userId = $userId;
    }

    public function sheets(): array
    {
        $sheets = [];
        
        // Create sheets for each month (1-12)
        for ($month = 1; $month <= 12; $month++) {
            $sheets[] = new DailyReportMonthlySheet($this->year, $month, $this->userId);
        }

        return $sheets;
    }
}