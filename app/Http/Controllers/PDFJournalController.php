<?php
namespace App\Http\Controllers;

use App\Models\Intern;
use App\Models\Journal;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class PDFJournalController extends Controller
{
    public function downloadpdf() {
        if (Auth::guard('intern')->check()) {
            $journal = Journal::with(['intern', 'intern.internDivision', 'intern.supervisor', 'intern.school'])
                ->where('intern_id', Auth::guard('intern')->user()->id)
                ->get();
        } elseif (Auth::guard('web')->check()) {
            $journal = Journal::with(['intern', 'intern.internDivision', 'intern.supervisor', 'intern.school'])
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
        
        if (Auth::guard('intern')->check()) {
            $journal = Journal::with(['intern', 'intern.internDivision', 'intern.supervisor', 'intern.school'])
                ->where('intern_id', Auth::guard('intern')->user()->id)
                ->whereMonth('entry_date', $month)
                ->whereYear('entry_date', $year)
                ->orderBy('entry_date', 'asc')
                ->get();
        } elseif (Auth::guard('web')->check()) {
            $journal = Journal::with(['intern', 'intern.internDivision', 'intern.supervisor', 'intern.school'])
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
    public function downloadSupervisorJournalsAll(Request $request)
    {
        if (!Auth::guard('web')->check()) {
            return abort(403, 'Unauthorized');
        }

        /** @var User $supervisor */
        $supervisor = Auth::guard('web')->user();

        $journals = Journal::with(['intern', 'intern.internDivision', 'intern.supervisor', 'intern.school'])
            ->whereHas('intern', function ($query) use ($supervisor) {
                $query->where('supervisor_id', $supervisor->id);
            })
            ->orderBy('entry_date', 'asc')
            ->get();
            
        $data = [
            'title' => 'Laporan Jurnal Lengkap - Bimbingan ' . $supervisor->name,
            'journal' => $journals,
            'period' => 'Semua Periode'
        ];
        
        $pdf = Pdf::loadview('journalPDF', $data);
        $filename = 'laporan-jurnal-bimbingan-' . str_replace(' ', '_', strtolower($supervisor->name)) . '.pdf';

        return $pdf->download($filename);
    }

    private function authorizeSupervisor(Intern $intern)
    {
        $supervisor = Auth::guard('web')->user();
        if ($intern->supervisor_id !== $supervisor->id) {
            abort(403, 'Anda tidak memiliki akses ke data anak magang ini.');
        }
    }


    public function downloadSupervisorInternJournalsAll(Request $request, Intern $intern)
    {
        $this->authorizeSupervisor($intern); 

        $journals = Journal::with(['intern', 'intern.internDivision', 'intern.supervisor', 'intern.school'])
            ->where('intern_id', $intern->id)
            ->orderBy('entry_date', 'asc')
            ->get();
            
        $data = [
            'title' => 'Laporan Jurnal Lengkap - ' . $intern->name,
            'journal' => $journals,
            'period' => 'Semua Periode'
        ];
        
        $pdf = Pdf::loadview('journalPDF', $data);
        $filename = 'laporan-jurnal-lengkap-' . str_replace(' ', '_', strtolower($intern->name)) . '.pdf';

        return $pdf->download($filename);
    }


    public function downloadSupervisorInternMonthlyPdf(Request $request, Intern $intern)
    {
        $this->authorizeSupervisor($intern); 

        $validated = $request->validate([
            'month' => 'required|integer|between:1,12',
            'year'  => 'required|integer|digits:4',
        ]);
        
        $journals = Journal::with(['intern', 'intern.internDivision', 'intern.supervisor', 'intern.school'])
            ->where('intern_id', $intern->id)
            ->whereMonth('entry_date', $validated['month'])
            ->whereYear('entry_date', $validated['year'])
            ->orderBy('entry_date', 'asc')
            ->get();
            
        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        
        $monthName = $monthNames[$validated['month']] . ' ' . $validated['year'];
        
        $data = [
            'title' => 'Laporan Jurnal ' . $intern->name . ' - ' . $monthName,
            'journal' => $journals,
            'period' => 'Periode ' . $monthName
        ];
        
        $pdf = Pdf::loadview('journalPDF', $data);
        $filename = 'laporan-jurnal-' . str_replace(' ', '_', strtolower($intern->name)) . '-' . strtolower(str_replace(' ', '-', $monthName)) . '.pdf';
        
        return $pdf->download($filename);
    }
}