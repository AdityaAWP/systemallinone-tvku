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

Route::get('/', function () {
    return redirect()->route('filament.admin.pages.dashboard');
});
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

Route::get('/login', function () {
    return redirect()->route('filament.admin.auth.login');
})->name('login');

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

