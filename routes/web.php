<?php

use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\PDFController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LetterAttachmentController;
use App\Http\Controllers\PDFLoanController;
use App\Http\Controllers\LeaveTokenActionController;
use App\Http\Controllers\LeaveDetailController;
use App\Http\Controllers\PDFAssignmentController;
use App\Http\Controllers\InternController;
use App\Http\Controllers\JournalReportController;
use App\Http\Controllers\PDFJournalController;
use Illuminate\Support\Facades\Storage;

Route::get('/info', function () {
    return gd_info();
});

// Google Auth Routes
Route::get('auth/google', [GoogleController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('auth/google/callback', [GoogleController::class, 'handleGoogleCallback']);

// Custom error route
Route::get('error/403', function () {
    return response()->view('errors.403', [], 403);
})->name('error.403');


Route::get('download', [PDFController::class, 'downloadpdf'])->name('overtime.report');
Route::get('downloadpdf/{id}', [PDFController::class, 'userpdf'])->name('overtime.single');

Route::get('download/monthly', [PDFController::class, 'downloadMonthlyPdf'])
    ->name('overtime.monthly');

//Peminjaman
Route::get('download/{id}', [PDFLoanController::class, 'userpdf'])->name('loanitem.single');
Route::get('downloadassignment/{id}', [PDFAssignmentController::class, 'single'])->name('assignment.single');
// Leave Routes Baru
Route::get('/leave/approve-by-token/{token}', [LeaveTokenActionController::class, 'approve'])
    ->name('leave.approve.token');
    
Route::get('/leave/reject-by-token/{token}', [LeaveTokenActionController::class, 'reject'])
    ->name('leave.reject.token');

// Letter Attachment Routes
Route::get('/attachment/{attachment}/download', [LetterAttachmentController::class, 'download'])
    ->middleware(['auth'])
    ->name('attachment.download');

Route::delete('/attachment/{attachment}/delete', [LetterAttachmentController::class, 'delete'])
    ->middleware(['auth'])
    ->name('attachment.delete');

//lihat detail email staff
Route::get('/leave/detail/{id}', [LeaveDetailController::class, 'show'])
    ->name('leave.detail')
    ->middleware('auth');

//route pdf intern
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/interns/pdf', [InternController::class, 'generatePdf'])->name('interns.pdf');
    Route::get('/interns/excel', [InternController::class, 'downloadExcel'])->name('interns.excel');
});

Route::get('pdfjournal', [PDFJournalController::class, 'downloadpdf'])->name('journal.report');
Route::get('pdfjournal/monthly', [PDFJournalController::class, 'downloadMonthlyPdf'])
    ->name('journal.monthly');

Route::get('/overtime/user/{user_id}/monthly/pdf', [PDFController::class, 'downloadUserMonthlyPdf'])
    ->name('overtime.user.monthly.pdf');

Route::get('/assignments/{id}/xml', [PDFAssignmentController::class, 'generateXml'])->name('assignment.xml');

    Route::get('/reports/journal/user', [JournalReportController::class, 'downloadUserReport'])->name('journal.report.user');


// Add this to your routes/web.php file

// Add this to your routes/web.php file

Route::middleware(['auth'])->group(function () {
    // ZIP backup download (Spatie package)
    Route::get('/backup/download/{file}', function ($file) {
        $backupDisk = config('backup.backup.destination.disks.0', 'local');
        $disk = Storage::disk($backupDisk);
        $filePath = 'Laravel/' . $file;
        
        if (!$disk->exists($filePath)) {
            abort(404, 'Backup file not found');
        }
        
        return response()->streamDownload(function () use ($disk, $filePath) {
            $stream = $disk->readStream($filePath);
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, $file, [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => 'attachment; filename="' . $file . '"'
        ]);
    })->name('backup.download');
    
    // SQL backup download
    Route::get('/sql-backup/download/{file}', function ($file) {
        $filePath = storage_path('app/backups/' . $file);
        
        if (!file_exists($filePath)) {
            abort(404, 'SQL backup file not found');
        }
        
        return response()->download($filePath, $file, [
            'Content-Type' => 'application/sql',
        ]);
    })->name('sql-backup.download');
});