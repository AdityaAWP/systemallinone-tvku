<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Carbon\Carbon;

class InternsByStatusSheetExport implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize
{
    protected $collection;
    protected $statusTitle;
    protected $includeStatusColumn; // Our new flag

    public function __construct(string $statusTitle, Collection $collection, bool $includeStatusColumn = false)
    {
        $this->statusTitle = $statusTitle;
        $this->collection = $collection;
        $this->includeStatusColumn = $includeStatusColumn;
    }

    public function collection(): Collection
    {
        return $this->collection;
    }

    public function title(): string
    {
        return $this->statusTitle;
    }

    public function headings(): array
    {
        // Base headers
        $headings = [
            'ID', 'Nama', 'Email', 'Jenjang Pendidikan', 'Asal Sekolah/Universitas',
            'Divisi', 'No. Telepon', 'Pembimbing TVKU', 'Pembimbing Asal',
            'Telepon Pembimbing', 'Tanggal Mulai', 'Tanggal Selesai',
        ];

        // Conditionally add the Status header
        if ($this->includeStatusColumn) {
            $headings[] = 'Status';
        }

        return $headings;
    }

    public function map($intern): array
    {
        // Base mapping
        $row = [
            $intern->id,
            $intern->name,
            $intern->email,
            $intern->institution_type,
            $intern->school ? $intern->school->name : '-',
            $intern->internDivision ? $intern->internDivision->name : '-',
            $intern->no_phone,
            $intern->institution_supervisor,
            $intern->college_supervisor,
            $intern->college_supervisor_phone,
            $intern->start_date ? Carbon::parse($intern->start_date)->format('d/m/Y') : '',
            $intern->end_date ? Carbon::parse($intern->end_date)->format('d/m/Y') : '',
        ];

        // Conditionally calculate and add the status
        if ($this->includeStatusColumn) {
            $now = Carbon::now();
            $start = Carbon::parse($intern->start_date);
            $end = Carbon::parse($intern->end_date);

            $status = 'Status Tidak Diketahui'; // Default
            if ($end) {
                $hampirStart = $end->copy()->subMonth();
                if ($now->isBefore($start)) {
                    $status = 'Akan Datang';
                } elseif ($now->isAfter($end)) {
                    $status = 'Selesai';
                } elseif ($now->isBetween($hampirStart, $end)) {
                    $status = 'Hampir Selesai';
                } else {
                    $status = 'Aktif';
                }
            }
            $row[] = $status;
        }

        return $row;
    }
}