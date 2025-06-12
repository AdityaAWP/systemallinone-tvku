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
    protected int $userId;

    public function __construct(int $year, int $userId)
    {
        $this->year = $year;
        $this->userId = $userId;
    }

    public function sheets(): array
    {
        $sheets = [];

        for ($month = 1; $month <= 12; $month++) {
            // This check prevents creating empty sheets for months with no leave data.
            $hasData = Leave::query()
                ->where('user_id', $this->userId)
                ->where(function (Builder $query) use ($month) {
                    $query->where(function(Builder $q) use ($month) {
                        $q->whereYear('from_date', $this->year)
                          ->whereMonth('from_date', $month);
                    })->orWhere(function(Builder $q) use ($month) {
                        $q->whereYear('to_date', $this->year)
                          ->whereMonth('to_date', $month);
                    });
                })
                ->exists();

            if ($hasData) {
                $sheets[] = new LeaveMonthlySheet($this->year, $month, $this->userId);
            }
        }

        return $sheets;
    }
}