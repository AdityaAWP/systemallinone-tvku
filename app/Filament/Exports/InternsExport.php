<?php

namespace App\Filament\Exports;

use App\Models\Intern;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class InternsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return Intern::with('school')->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nama',
            'Email',
            'Sekolah/Instansi',
            'Divisi',
            'No. Telepon',
            'Pembimbing Asal',
            'Pembimbing TVKU',
            'Telepon Pembimbing',
            'Tanggal Mulai',
            'Tanggal Selesai',
        ];
    }

    public function map($intern): array
    {
        return [
            $intern->id,
            $intern->name,
            $intern->email,
            $intern->school ? $intern->school->name : '',
            $intern->division,
            $intern->no_phone,
            $intern->institution_supervisor,
            $intern->college_supervisor,
            $intern->college_supervisor_phone,
            $intern->start_date ? $intern->start_date->format('d/m/Y') : '',
            $intern->end_date ? $intern->end_date->format('d/m/Y') : '',
        ];
    }
}