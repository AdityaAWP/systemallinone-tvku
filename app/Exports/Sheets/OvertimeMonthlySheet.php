<?php

namespace App\Exports\Sheets;

use App\Models\Overtime;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class OvertimeMonthlySheet implements FromQuery, WithTitle, WithHeadings, WithMapping
{
    private int $year;
    private int $month;
    private int $userId;

    public function __construct(int $year, int $month, int $userId)
    {
        $this->year = $year;
        $this->month = $month;
        $this->userId = $userId;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(): Builder
    {
        return Overtime::query()
            ->with('user')
            ->where('user_id', $this->userId)
            ->whereYear('tanggal_overtime', $this->year)
            ->whereMonth('tanggal_overtime', $this->month)
            ->orderBy('tanggal_overtime');
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return Carbon::create()->month($this->month)->translatedFormat('F');
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Nama Karyawan',
            'Tanggal Lembur',
            'Status Hari',
            'Mulai Lembur',
            'Selesai Lembur',
            'Durasi Lembur',
            'Deskripsi',
        ];
    }

    /**
     * @param \App\Models\Overtime $overtime
     * @return array
     */
    public function map($overtime): array
    {
        return [
            $overtime->user->name,
            Carbon::parse($overtime->tanggal_overtime)->format('d F Y'),
            $overtime->is_holiday ? 'Hari Libur' : 'Hari Kerja',
            Carbon::parse($overtime->check_in)->format('H:i'),
            Carbon::parse($overtime->check_out)->format('H:i'),
            "{$overtime->overtime_hours} jam {$overtime->overtime_minutes} menit",
            $overtime->description,
        ];
    }
}