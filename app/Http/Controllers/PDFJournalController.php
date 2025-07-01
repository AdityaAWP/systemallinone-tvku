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
        // Check which guard is authenticated
        if (Auth::guard('intern')->check()) {
            // For intern users - show only their journals
            $journal = Journal::with(['intern', 'intern.internDivision'])
                ->where('intern_id', Auth::guard('intern')->user()->id)
                ->get();
        } elseif (Auth::guard('web')->check()) {
            // For admin_magang and super_admin - show all journals
            $journal = Journal::with(['intern', 'intern.internDivision'])
                ->get();
        } else {
            return abort(403, 'Unauthorized');
        }
        
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
        
        // Check which guard is authenticated
        if (Auth::guard('intern')->check()) {
            // For intern users - show only their journals
            $journal = Journal::with(['intern', 'intern.internDivision'])
                ->where('intern_id', Auth::guard('intern')->user()->id)
                ->whereMonth('entry_date', $month)
                ->whereYear('entry_date', $year)
                ->orderBy('entry_date', 'asc')
                ->get();
        } elseif (Auth::guard('web')->check()) {
            // For admin_magang and super_admin - show all journals for the selected month/year
            $journal = Journal::with(['intern', 'intern.internDivision'])
                ->whereMonth('entry_date', $month)
                ->whereYear('entry_date', $year)
                ->orderBy('entry_date', 'asc')
                ->get();
        } else {
            return abort(403, 'Unauthorized');
        }
        
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