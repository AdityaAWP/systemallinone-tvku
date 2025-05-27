<?php

namespace App\Http\Controllers;

use App\Models\Journal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PDF;

class PDFJournalController extends Controller
{
    public function downloadpdf() {
        // Use Auth::guard('intern')->user()->id instead of Auth::user()->id
        $journal = Journal::with('intern')
            ->where('intern_id', Auth::guard('intern')->user()->id)
            ->get();
        
        $data = [
            'title' => 'Laporan Jurnal',
            'overtime' => $journal
        ];
        
        $pdf = PDF::loadview('journalPDF', $data);
        return $pdf->download('laporan-jurnal.pdf');
    }
}
