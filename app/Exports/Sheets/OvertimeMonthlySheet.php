<?php

namespace App\Exports\Sheets;

use App\Models\Overtime;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Events\BeforeSheet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use App\Models\User;

class OvertimeMonthlySheet implements FromQuery, WithTitle, WithHeadings, WithMapping, WithCustomStartCell, WithEvents
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
            Carbon::parse($overtime->tanggal_overtime)->format('d F Y'),
            $overtime->is_holiday ? 'Hari Libur' : 'Hari Kerja',
            Carbon::parse($overtime->check_in)->format('H:i'),
            Carbon::parse($overtime->check_out)->format('H:i'),
            "{$overtime->overtime_hours} jam {$overtime->overtime_minutes} menit",
            $overtime->description,
        ];
    }

    public function registerEvents(): array
    {
        return [
            BeforeSheet::class => function (BeforeSheet $event) {
                if ($this->userId) {
                    $user = \App\Models\User::with('division')->find($this->userId);
                    $sheet = $event->getSheet();
                    $sheet->setCellValue('A1', 'Nama');
                    $sheet->setCellValue('B1', $user?->name ?? '-');
                    $sheet->setCellValue('A2', 'NPP');
                    $sheet->setCellValue('B2', $user?->npp ?? '-');
                    $sheet->setCellValue('A3', 'Divisi');
                    $divisions = ($user?->divisions && $user?->divisions->count() > 0)
                        ? $user?->divisions?->pluck('name')->implode(', ')
                        : ($user?->division?->name ?? '-');
                    $sheet->setCellValue('B3', $divisions ?: '-');
                    $sheet->setCellValue('A4', 'Jabatan');
                    $sheet->setCellValue('B4', $user?->position ?? '-');
                    // Baris 5 dibiarkan kosong sebagai spasi
                }
            },
        ];
    }

    public function startCell(): string
    {
        // Jika export per user, data mulai dari baris 6 (setelah header + 1 baris kosong)
        // Jika export semua staff, data mulai dari baris 1
        return $this->userId ? 'A6' : 'A1';
    }
}