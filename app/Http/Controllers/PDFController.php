<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Overtime;
use Illuminate\Support\Facades\Auth;
use PDF;
use Carbon\Carbon;

class PDFController extends Controller
{
    /**
     * Check if user has any manager role
     */
    private function isManager($user): bool
    {
        return $user->roles()->where('name', 'like', 'manager%')->exists();
    }

    /**
     * Check if user has any kepala role
     */
    private function isKepala($user): bool
    {
        return $user->roles()->where('name', 'like', 'kepala%')->exists();
    }

    // Download semua PDF (existing function)
    public function downloadpdf()
    {
        $overtime = Overtime::with('user')
            ->where('user_id', Auth::user()->id)
            ->get();

        $data = [
            'title' => 'Laporan Lembur',
            'overtime' => $overtime
        ];

        $pdf = PDF::loadview('overtimePDF', $data);
        return $pdf->download('laporan-lembur.pdf');
    }

    // Download PDF berdasarkan bulan dan tahun
    public function downloadMonthlyPdf(Request $request)
    {
        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);

        $overtime = Overtime::with('user')
            ->where('user_id', Auth::user()->id)
            ->whereMonth('tanggal_overtime', $month)
            ->whereYear('tanggal_overtime', $year)
            ->orderBy('tanggal_overtime', 'asc')
            ->get();

        // Format nama bulan dalam bahasa Indonesia
        $monthNames = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember'
        ];

        $monthName = $monthNames[$month] . ' ' . $year;

        $data = [
            'title' => 'Surat Permohonan Ijin Lembur - ' . $monthName,
            'overtime' => $overtime,
            'period' => $monthName
        ];

        $pdf = PDF::loadview('overtimePDF', $data);
        $filename = 'surat-lembur-' . strtolower(str_replace(' ', '-', $monthName)) . '.pdf';

        return $pdf->download($filename);
    }

    // Download PDF individual (existing function)
    public function userpdf($id)
    {
        $overtime = Overtime::with('user')
            ->where('id', $id)
            ->where('user_id', Auth::user()->id)
            ->get();

        if ($overtime->isEmpty()) {
            return redirect()->back()->with('error', 'Record not found or unauthorized access');
        }

        $data = [
            'title' => 'Laporan Lembur Individual',
            'overtime' => $overtime
        ];

        $pdf = PDF::loadView('overtimePDF', $data);

        return response($pdf->output())
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="laporan-lembur-' . $id . '.pdf"');
    }

    public function downloadUserMonthlyPdf(Request $request, $user_id)
    {
        $user = Auth::user();
        
        // Pastikan hanya HRD, Manager, atau Kepala yang bisa mengakses
        if (!$user->hasRole('hrd') && !$this->isManager($user) && !$this->isKepala($user)) {
            return redirect()->back()->with('error', 'Unauthorized access');
        }

        // Jika bukan HRD, validasi akses divisi
        if (!$user->hasRole('hrd')) {
            // Get user yang akan didownload datanya
            $targetUser = \App\Models\User::find($user_id);
            if (!$targetUser) {
                return redirect()->back()->with('error', 'User tidak ditemukan');
            }

            // Get divisi yang dikelola user yang login
            $userDivisionIds = $user->divisions()->pluck('divisions.id')->toArray();
            
            // Jika tidak ada divisi dari many-to-many, fallback ke primary division
            if (empty($userDivisionIds) && $user->division_id) {
                $userDivisionIds = [$user->division_id];
            }

            // Cek apakah target user ada di divisi yang dikelola
            if (!in_array($targetUser->division_id, $userDivisionIds)) {
                return redirect()->back()->with('error', 'Anda tidak memiliki akses untuk mendownload data user dari divisi lain');
            }
        }

        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);

        $overtime = Overtime::with(['user', 'user.division'])
            ->where('user_id', $user_id)
            ->whereMonth('tanggal_overtime', $month)
            ->whereYear('tanggal_overtime', $year)
            ->orderBy('tanggal_overtime', 'asc')
            ->get();

        if ($overtime->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada data lembur untuk periode yang dipilih');
        }

        // Format nama bulan dalam bahasa Indonesia
        $monthNames = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember'
        ];

        $monthName = $monthNames[$month] . ' ' . $year;
        $userName = $overtime->first()->user->name;

        $data = [
            'title' => 'Surat Permohonan Ijin Lembur - ' . $userName . ' - ' . $monthName,
            'overtime' => $overtime,
            'period' => $monthName
        ];

        $pdf = PDF::loadview('overtimePDF', $data);
        $filename = 'surat-lembur-' . strtolower(str_replace([' ', '.'], '-', $userName)) . '-' . strtolower(str_replace(' ', '-', $monthName)) . '.pdf';

        return $pdf->download($filename);
    }
}
