<?php

namespace App\Exports\Sheets;

use App\Models\Leave;
use App\Filament\Resources\LeaveResource;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use App\Models\User;

class LeaveMonthlySheet implements FromQuery, WithTitle, WithHeadings, WithMapping, WithCustomStartCell, WithEvents
{
    private int $year;
    private int $month;
    private ?int $userId;
    private ?array $divisionIds;

    public function __construct(int $year, int $month, ?int $userId = null, ?array $divisionIds = null)
    {
        $this->year = $year;
        $this->month = $month;
        $this->userId = $userId;
        $this->divisionIds = $divisionIds;
    }

    /**
     * @return Builder
     */
    public function query(): Builder
    {
        // This query finds leave requests that are active within the specified month.
        // It correctly handles leaves that might start in a previous month or end in a future one.
        $query = Leave::query()
            ->with(['user', 'user.division', 'user.divisions']); // Tambahkan eager loading untuk relasi
            
        // Jika userId disediakan, filter berdasarkan user_id
        if ($this->userId !== null) {
            $query->where('user_id', $this->userId);
        }
        
        // Jika divisionIds disediakan (untuk manager/kepala), filter berdasarkan divisi yang dikelola
        if ($this->divisionIds !== null && !empty($this->divisionIds)) {
            $query->whereHas('user', function ($q) {
                $q->whereIn('division_id', $this->divisionIds);
            });
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
            'NPP',
            'Divisi',
            'Jabatan',
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

        // Perbaikan untuk menghindari error "name" on null
        $divisionName = '-';
        if ($leave->user && $leave->user->divisions && $leave->user->divisions->count() > 0) {
            $divisionName = $leave->user->divisions->pluck('name')->implode(', ');
        } elseif ($leave->user && $leave->user->division) {
            $divisionName = $leave->user->division->name;
        }

        return [
            $leave->user->name ?? '-',
            $leave->user->npp ?? '-',
            $divisionName,
            $leave->user->position ?? '-',
            // Gunakan map untuk mendapatkan nama jenis cuti yang sesuai
            $leaveTypeMap[$leave->leave_type] ?? 'Tidak Diketahui',
            Carbon::parse($leave->from_date)->format('d F Y'),
            Carbon::parse($leave->to_date)->format('d F Y'),
            $workingDays . ' hari',
            ucfirst($leave->status),
            Carbon::parse($leave->created_at)->format('d M Y, H:i'),
            $leave->reason ?? '-',
        ];
    }

    public function startCell(): string
    {
        // Jika export per user, data mulai dari baris 6 (setelah header + 1 baris kosong)
        // Jika export semua staff, data mulai dari baris 1
        return $this->userId ? 'A6' : 'A1';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                if ($this->userId) {
                    $user = User::with(['division', 'divisions'])->find($this->userId); // Tambahkan eager loading
                    if ($user) {
                        $event->sheet->setCellValue('A1', 'Nama');
                        $event->sheet->setCellValue('B1', $user->name ?? '-');
                        $event->sheet->setCellValue('A2', 'NPP');
                        $event->sheet->setCellValue('B2', $user->npp ?? '-');
                        $event->sheet->setCellValue('A3', 'Divisi');
                        
                        // Perbaikan untuk menghindari error
                        $divisions = '-';
                        if ($user->divisions && $user->divisions->count() > 0) {
                            $divisions = $user->divisions->pluck('name')->implode(', ');
                        } elseif ($user->division) {
                            $divisions = $user->division->name;
                        }
                        
                        $event->sheet->setCellValue('B3', $divisions);
                        $event->sheet->setCellValue('A4', 'Jabatan');
                        $event->sheet->setCellValue('B4', $user->position ?? '-');
                        // Baris 5 dibiarkan kosong sebagai spasi
                    }
                }
            }
        ];
    }
}