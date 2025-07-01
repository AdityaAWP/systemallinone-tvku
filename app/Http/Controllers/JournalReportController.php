<?php

namespace App\Http\Controllers;

use App\Exports\JournalReportExport;
use App\Models\Intern;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class JournalReportController extends Controller
{
    public function downloadUserReport(Request $request)
    {
        $validated = $request->validate([
            'intern_id' => 'required|integer|exists:interns,id',
            'month'     => 'required|integer|between:1,12',
            'year'      => 'required|integer|digits:4',
        ]);

        $intern = Intern::findOrFail($validated['intern_id']);

        // --- REVISED and SAFER WAY to get the month name ---
        // Create a Carbon instance safely from the year and month provided.
        // We set the day to 1 to avoid "invalid date" errors (e.g., Feb 30th).
        $monthName = Carbon::createFromDate($validated['year'], $validated['month'], 1)->translatedFormat('F');

        $internName = str_replace(' ', '_', $intern->name);
        $filename = "laporan_jurnal_{$internName}_{$monthName}_{$validated['year']}.xlsx";

        return Excel::download(
            new JournalReportExport($validated['intern_id'], $validated['month'], $validated['year']),
            $filename
        );
    }
}