<?php

namespace App\Http\Controllers;

use App\Models\LoanItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use PDF;

class PDFLoanController extends Controller
{
    public function userpdf($id) {
        $loanitem = LoanItem::with(['user', 'items'])
            ->where('id', $id)
            ->where('user_id', Auth::user()->id)
            ->first();
            
        if(!$loanitem) {
            return redirect()->back()->with('error', 'Record not found or unauthorized access');
        }
        
        // Debug: Log the items relationship
        Log::info('Loan items debug:', [
            'item_count' => $loanitem->items->count(),
            'items' => $loanitem->items->toArray()
        ]);
        
        $data = [
            'title' => 'Laporan Peminjaman',
            'loanitem' => $loanitem
        ];
        
        $pdf = PDF::loadView('loanitemPDF', $data);
        return response($pdf->output())
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="laporan-peminjaman-'.$id.'.pdf"');
    }
}
