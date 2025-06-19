<?php

namespace App\Filament\Exports;

use App\Models\Intern;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class InternsExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    protected $institutionType;
    
    /**
     * Constructor dengan parameter filter tipe institusi
     * 
     * @param string $institutionType
     */
    public function __construct($institutionType = 'all')
    {
        $this->institutionType = $institutionType;
    }
    
    /**
     * @return string
     */
    public function title(): string
    {
        return match($this->institutionType) {
            'Perguruan Tinggi' => 'Data Magang Perguruan Tinggi',
            'SMA/SMK' => 'Data Magang SMA/SMK',
            default => 'Data Magang'
        };
    }
    
    public function collection()
    {
        $query = Intern::with(['school', 'internDivision']);
        
        // Filter berdasarkan tipe institusi
        if ($this->institutionType !== 'all') {
            $query->whereHas('school', function ($q) {
                $q->where('type', $this->institutionType);
            });
        }
        
        return $query->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nama',
            'Email',
            'Tipe Institusi',
            'Sekolah/Instansi',
            'Divisi',
            'No. Telepon',
            'Pembimbing TVKU',
            'Pembimbing Asal',
            'Telepon Pembimbing',
            'Tanggal Mulai',
            'Tanggal Selesai',
            'Status',
        ];
    }

    public function map($intern): array
    {
        // Menghitung status magang
        $now = now();
        $start = $intern->start_date;
        $end = $intern->end_date;
        $hampirStart = $end->copy()->subMonth();

        if ($now->lessThan($start)) {
            $status = 'Datang';
        } elseif ($now->greaterThanOrEqualTo($hampirStart) && $now->lessThanOrEqualTo($end)) {
            $status = 'Hampir';
        } elseif ($now->between($start, $hampirStart->subSecond())) {
            $status = 'Active';
        } else {
            $status = 'Selesai';
        }
        
        return [
            $intern->id,
            $intern->name,
            $intern->email,
            $intern->school ? $intern->school->type : '',
            $intern->school ? $intern->school->name : '',
            $intern->internDivision ? $intern->internDivision->name : '',
            $intern->no_phone,
            $intern->institution_supervisor,
            $intern->college_supervisor,
            $intern->college_supervisor_phone,
            $intern->start_date ? $intern->start_date->format('d/m/Y') : '',
            $intern->end_date ? $intern->end_date->format('d/m/Y') : '',
            $status,
        ];
    }
}