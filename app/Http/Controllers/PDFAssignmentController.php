<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use Illuminate\Http\Request;
use PDF;

class PDFAssignmentController extends Controller
{
    public function generateSinglePDF(Assignment $assignment)
    {
        // Load the PDF view
        $pdf = PDF::loadView('assignmentPDF', compact('assignment'));
        
        // Set paper size ke A4
        $pdf->setPaper('a4', 'portrait');
        
        // Nama file PDF yang akan di-download
        $filename = 'SPP-'.$assignment->spp_number.'.pdf';
        
        // Mengembalikan file PDF untuk di-download
        return $pdf->download($filename);
        
        // Jika ingin menampilkan PDF di browser tanpa download:
        // return $pdf->stream($filename);
    }

}
