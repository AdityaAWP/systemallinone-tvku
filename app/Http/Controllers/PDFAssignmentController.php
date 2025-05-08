<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use PDF;

class PDFAssignmentController extends Controller
{
    public function single($id)
    {
        $assignment = Assignment::with(['approver'])
            ->where('id', $id)
            ->first();

        if (!$assignment) {
            return redirect()->back()->with('error', 'Assignment not found');
        }

        $data = [
            'title' => 'Assignment Report',
            'assignment' => $assignment
        ];

        $pdf = PDF::loadView('assignmentPDF', $data);
        
        return response($pdf->output())
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="assignment-'.$id.'.pdf"');
    }
}