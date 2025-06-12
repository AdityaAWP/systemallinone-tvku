<?php

namespace App\Exports;

use App\Exports\Sheets\OvertimeMonthlySheet;
use App\Models\Overtime;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class OvertimeYearlyExport implements WithMultipleSheets
{
    use Exportable;

    protected int $year;
    protected int $userId;

    public function __construct(int $year, int $userId)
    {
        $this->year = $year;
        $this->userId = $userId;
    }

    /**
     * @return array
     */
    public function sheets(): array
    {
        $sheets = [];

        for ($month = 1; $month <= 12; $month++) {
            $hasData = Overtime::query()
                ->where('user_id', $this->userId)
                ->whereYear('tanggal_overtime', $this->year)
                ->whereMonth('tanggal_overtime', $month)
                ->exists();

            if ($hasData) {
                $sheets[] = new OvertimeMonthlySheet($this->year, $month, $this->userId);
            }
        }

        return $sheets;
    }
}