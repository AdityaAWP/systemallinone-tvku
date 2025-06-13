<?php

namespace App\Exports\Sheets;

use App\Models\DailyReport;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeSheet;
use Illuminate\Support\Carbon;

class DailyReportMonthlySheet implements FromQuery, WithTitle, WithHeadings, WithMapping, WithCustomStartCell, WithEvents
{
    private int $year;
    private int $month;
    private ?int $userId;

    public function __construct(int $year, int $month, ?int $userId = null)
    {
        $this->year = $year;
        $this->month = $month;
        $this->userId = $userId;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query()
    {
        $query = DailyReport::query()
            ->with('user')
            ->whereYear('entry_date', $this->year)
            ->whereMonth('entry_date', $this->month)
            ->orderBy('entry_date');

        // Jika userId disediakan, filter berdasarkan user_id
        if ($this->userId !== null) {
            $query->where('user_id', $this->userId);
        }

        return $query;
    }

    /**
     * @return string
     */
    public function title(): string
    {
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
            'Waktu Mulai Bekerja',
            'Waktu Selesai Bekerja',
            'Jam Kerja',
            'Keterangan',
        ];
    }

    /**
     * @param DailyReport $row
     * @return array
     */
    public function map($row): array
    {
        return [
            $row->user->name ?? '',
            $row->entry_date ? Carbon::parse($row->entry_date)->format('d F Y') : '',
            $row->check_in ? Carbon::parse($row->check_in)->format('H:i') : '',
            $row->check_out ? Carbon::parse($row->check_out)->format('H:i') : '',
            "{$row->work_hours_component} jam {$row->work_minutes_component} menit",
            strip_tags($row->description ?? ''), // Remove HTML tags
        ];
    }

    public function startCell(): string
    {
        return 'A6'; // Data dimulai dari baris ke-6 (setelah spasi)
    }

    public function registerEvents(): array
    {
        return [
            BeforeSheet::class => function (BeforeSheet $event) {
                // Ambil user jika userId diset
                $user = null;
                if ($this->userId) {
                    $user = \App\Models\User::with('division')->find($this->userId);
                }
                $sheet = $event->getSheet();
                $sheet->setCellValue('A1', 'Nama');
                $sheet->setCellValue('B1', $user?->name ?? '-');
                $sheet->setCellValue('A2', 'NPP');
                $sheet->setCellValue('B2', $user?->npp ?? '-');
                $sheet->setCellValue('A3', 'Divisi');
                $sheet->setCellValue('B3', $user?->division?->name ?? '-');
                $sheet->setCellValue('A4', 'Jabatan');
                $sheet->setCellValue('B4', $user?->position ?? '-');
                // Baris 5 dibiarkan kosong sebagai spasi
            },
        ];
    }
}