<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class DailyReportTemplateExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    public function array(): array
    {
        // Return sample data for template
        return [
            [
                'Staff 3',
                'v1@example.com',
                '04/08/2025',
                '09:00',
                '12:00',
                'malam'
            ],
            [
                'Staff 1', 
                'staff1@example.com',
                '04/08/2025',
                '08:00',
                '17:00',
                'Mengerjakan project development sistem, meeting dengan tim, review code'
            ]
        ];
    }

    public function headings(): array
    {
        return [
            'nama',
            'email',
            'tanggal',
            'waktu masuk',
            'waktu keluar',
            'deskripsi'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Style untuk header
        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['rgb' => '4472C4']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ]);

        // Style untuk data
        $sheet->getStyle('A2:F3')->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ]);

        // Wrap text untuk kolom deskripsi
        $sheet->getStyle('F:F')->getAlignment()->setWrapText(true);

        return $sheet;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15, // nama
            'B' => 25, // email
            'C' => 15, // tanggal
            'D' => 12, // waktu masuk
            'E' => 12, // waktu keluar
            'F' => 50, // deskripsi
        ];
    }
}
