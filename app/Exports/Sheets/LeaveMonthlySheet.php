<?php

namespace App\Exports\Sheets;

use App\Models\Leave;
use App\Filament\Resources\LeaveResource;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;

class LeaveMonthlySheet implements FromQuery, WithTitle, WithHeadings, WithMapping
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
     * @return Builder
     */
    public function query(): Builder
    {
        // This query finds leave requests that are active within the specified month.
        // It correctly handles leaves that might start in a previous month or end in a future one.
        $query = Leave::query()
            ->with('user');
            
        // Jika userId disediakan, filter berdasarkan user_id
        if ($this->userId !== null) {
            $query->where('user_id', $this->userId);
        }
        
        return $query->where(function (Builder $query) {
                $query->where(function(Builder $q) {
                    $q->whereYear('from_date', $this->year)
                      ->whereMonth('from_date', $this->month);
                })->orWhere(function(Builder $q) {
                    $q->whereYear('to_date', $this->year)
                      ->whereMonth('to_date', $this->month);
                });
            })
            ->orderBy('from_date');
    }

    /**
     * @return string
     */
    public function title(): string
    {
        // Set sheet title to the Indonesian month name (e.g., "Januari", "Februari")
        return Carbon::create()->month($this->month)->translatedFormat('F');
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Nama Karyawan',
            'Jenis Cuti',
            'Tanggal Mulai',
            'Tanggal Berakhir',
            'Jumlah Hari Kerja',
            'Status',
            'Tanggal Pengajuan',
            'Keterangan',
        ];
    }

    /**
     * @param mixed $leave
     * @return array
     */
    public function map($leave): array
    {
        $workingDays = LeaveResource::calculateWorkingDays($leave->from_date, $leave->to_date);

        $leaveTypeMap = [
            'casual' => 'Cuti Tahunan',
            'medical' => 'Cuti Sakit',
            'maternity' => 'Cuti Melahirkan',
            'other' => 'Cuti Lainnya',
        ];

        return [
            $leave->user->name,
            $leaveTypeMap[$leave->leave_type] ?? 'Tidak Diketahui',
            Carbon::parse($leave->from_date)->format('d F Y'),
            Carbon::parse($leave->to_date)->format('d F Y'),
            $workingDays . ' hari',
            ucfirst($leave->status),
            Carbon::parse($leave->created_at)->format('d M Y, H:i'),
            $leave->reason,
        ];
    }
}