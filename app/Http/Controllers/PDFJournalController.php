<?php

namespace App\Http\Controllers;

use App\Models\Journal;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class PDFJournalController extends Controller
{
    public function downloadpdf() {
        // Use Auth::guard('intern')->user()->id instead of Auth::user()->id
        $journal = Journal::with(['intern', 'intern.internDivision'])
            ->where('intern_id', Auth::guard('intern')->user()->id)
            ->get();
        
        $data = [
            'title' => 'Laporan Jurnal',
            'journal' => $journal
        ];
        
        $pdf = Pdf::loadview('journalPDF', $data);
        return $pdf->download('laporan-jurnal.pdf');
    }

    public function downloadMonthlyPdf(Request $request) {
        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);
        
        $journal = Journal::with(['intern', 'intern.internDivision'])
        ->where('intern_id', Auth::guard('intern')->user()->id)
        ->whereMonth('entry_date', $month)
            ->whereYear('entry_date', $year)
            ->orderBy('entry_date', 'asc')
            ->get();
        
        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        
        $monthName = $monthNames[$month] . ' ' . $year;
        
        $data = [
            'title' => 'Laporan Jurnal - ' . $monthName,
            'journal' => $journal,
            'period' => $monthName
        ];
        
        $pdf = Pdf::loadview('journalPDF', $data);
        $filename = 'laporan-jurnal-' . strtolower(str_replace(' ', '-', $monthName)) . '.pdf';
        
        return $pdf->download($filename);
    }
}
