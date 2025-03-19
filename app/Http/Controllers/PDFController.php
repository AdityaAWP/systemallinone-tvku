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
}
