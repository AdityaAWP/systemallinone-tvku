<?php

namespace App\Http\Controllers;

use App\Models\Intern;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Filament\Exports\InternsExport;

class InternController extends Controller
{
    public function generatePdf()
    {
        $interns = Intern::with('school')->get();
        $pdf = PDF::loadView('interns-pdf', compact('interns'));
        return $pdf->download('daftar-anak-magang.pdf');
    }

    public function downloadExcel()
    {
        return Excel::download(new InternsExport, 'daftar-anak-magang.xlsx');
    }
}