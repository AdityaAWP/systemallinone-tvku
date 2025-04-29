<?php

namespace App\Http\Controllers;

use App\Models\Leave;
use Illuminate\Support\Facades\Auth;

class LeaveDetailController extends Controller
{
    public function show($id)
    {
        $leave = Leave::with('user')->findOrFail($id);
        
        // Verifikasi bahwa user yang login adalah pemilik cuti atau admin
        if (Auth::id() !== $leave->user_id && !Auth::user()->hasRole(['manager', 'hrd'])) {
            abort(403, 'Unauthorized action.');
        }

        return view('leave.detail', compact('leave'));
    }
}