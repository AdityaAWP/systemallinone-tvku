<?php

namespace App\Http\Controllers;

use App\Models\Overtime;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PDF;

class PDFController extends Controller
{
    public function downloadpdf() {
        $overtime = Overtime::with('user')->where('user_id', Auth::user()->id)->get();

        $data = [
            'title' => 'Laporan Lembur',
            'overtime' => $overtime
        ];
        $pdf = PDF::loadview('overtimePDF', $data);
        return $pdf->download('laporan-lembur.pdf');
    }
    public function userpdf($id) {
        $overtime = Overtime::with('user')->where('id', $id)->get();
        if($overtime->isEmpty()) {
            return redirect()->back()->with('error', 'Record not found');
        }
        
        $data = [
            'title' => 'Laporan Lembur Individual',
            'overtime' => $overtime
        ];
        
        $pdf = PDF::loadView('overtimePDF', $data);
        
        return response($pdf->output())
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="laporan-lembur-'.$id.'.pdf"');
    }
}
