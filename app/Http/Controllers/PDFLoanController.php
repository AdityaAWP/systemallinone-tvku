<?php

namespace App\Http\Controllers;

use App\Models\LoanItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PDF;

class PDFLoanController extends Controller
{
    public function userpdf($id) {
        $loanitem = LoanItem::with('user')
                ->where('id', $id)
                ->where('user_id', Auth::user()->id)
                ->get();
    
    if($loanitem->isEmpty()) {
        return redirect()->back()->with('error', 'Record not found or unauthorized access');
    }
        
        $data = [
            'title' => 'Laporan Lembur Individual',
            'loanitem' => $loanitem
        ];
        
        $pdf = PDF::loadView('loanitemPDF', $data);
        
        return response($pdf->output())
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="laporan-peminjaman-'.$id.'.pdf"');
    }
}
