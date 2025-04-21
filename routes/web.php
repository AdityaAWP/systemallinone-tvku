<?php

use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\PDFController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LetterAttachmentController;

Route::get('/', function () {
    return redirect()->route('filament.admin.pages.dashboard');
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
Route::get('download/{id}', [PDFController::class, 'userpdf'])->name('overtime.single');

// routes/web.php
Route::get('/leave/action/{leave}/{user}/{action}', [App\Http\Controllers\LeaveActionController::class, 'handleAction'])
    ->name('leave.action')
    ->middleware('signed');

Route::get('/leave/approve/{leave}/{user}', [App\Http\Controllers\LeaveActionController::class, 'approve'])
    ->name('leave.approve')
    ->middleware('signed');

Route::get('/leave/reject/{leave}/{user}', [App\Http\Controllers\LeaveActionController::class, 'reject'])
    ->name('leave.reject')
    ->middleware('signed');

Route::get('/attachment/{attachment}/download', [LetterAttachmentController::class, 'download'])
    ->middleware(['auth'])
    ->name('attachment.download');
    
Route::delete('/attachment/{attachment}/delete', [LetterAttachmentController::class, 'delete'])
    ->middleware(['auth'])
    ->name('attachment.delete');