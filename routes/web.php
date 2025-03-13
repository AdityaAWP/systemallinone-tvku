<?php

use App\Http\Controllers\Auth\GoogleController;
use Illuminate\Support\Facades\Route;

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