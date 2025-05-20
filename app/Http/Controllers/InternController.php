<?php

namespace App\Http\Controllers;

use App\Models\Intern;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Filament\Exports\InternsExport;

class InternController extends Controller
{
    public function generatePdf(Request $request)
    {
        $type = $request->query('type', 'all');
        
        $query = Intern::with('school');
        
        // Filter berdasarkan tipe institusi
        if ($type !== 'all') {
            $query->whereHas('school', function ($q) use ($type) {
                $q->where('type', $type);
            });
        }
        
        $interns = $query->get();
        
        // Menyesuaikan judul PDF
        $title = match($type) {
            'Perguruan Tinggi' => 'Daftar Anak Magang Perguruan Tinggi',
            'SMA/SMK' => 'Daftar Anak Magang SMA/SMK',
            default => 'Daftar Anak Magang'
        };
        
        $pdf = PDF::loadView('interns-pdf', compact('interns', 'title'));
        
        // Menyesuaikan nama file
        $filename = match($type) {
            'Perguruan Tinggi' => 'daftar-anak-magang-perguruan-tinggi.pdf',
            'SMA/SMK' => 'daftar-anak-magang-smk.pdf',
            default => 'daftar-anak-magang.pdf'
        };
        
        return $pdf->download($filename);
    }

    public function downloadExcel(Request $request)
    {
        $type = $request->query('type', 'all');
        
        // Menyesuaikan nama file
        $filename = match($type) {
            'Perguruan Tinggi' => 'daftar-anak-magang-perguruan-tinggi.xlsx',
            'SMA/SMK' => 'daftar-anak-magang-smk.xlsx',
            default => 'daftar-anak-magang.xlsx'
        };
        
        return Excel::download(new InternsExport($type), $filename);
    }
}