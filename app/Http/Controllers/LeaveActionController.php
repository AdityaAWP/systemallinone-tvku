<?php

namespace App\Http\Controllers;

use App\Models\Leave;
use App\Models\User;
use App\Models\LeaveQuota;
use App\Notifications\LeaveStatusUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaveActionController extends Controller
{
    public function approve(Request $request, Leave $leave, User $user)
    {
        if ($user->hasRole('manager')) {
            $leave->approval_manager = true;
            
            // If HRD already approved or no HRD approval needed
            if ($leave->approval_hrd === true || $leave->approval_hrd === null) {
                $leave->status = 'approved';
            }
            
            $leave->save();
            $this->notifyStatusChange($leave);
            
            return view('leave.action-response', [
                'title' => 'Permintaan Cuti Disetujui',
                'message' => 'Anda telah menyetujui permintaan cuti dari ' . $leave->user->name,
                'status' => 'success'
            ]);
        }
        
        if ($user->hasRole('hrd')) {
            $leave->approval_hrd = true;
            
            // If Manager already approved or no manager approval needed
            if ($leave->approval_manager === true || $leave->approval_manager === null) {
                $leave->status = 'approved';
            }
            
            $leave->save();
            $this->notifyStatusChange($leave);
            
            return view('leave.action-response', [
                'title' => 'Permintaan Cuti Disetujui',
                'message' => 'Anda telah menyetujui permintaan cuti dari ' . $leave->user->name,
                'status' => 'success'
            ]);
        }
        
        return view('leave.action-response', [
            'title' => 'Tidak Diizinkan',
            'message' => 'Anda tidak memiliki izin untuk melakukan tindakan ini.',
            'status' => 'error'
        ]);
    }
    
    public function reject(Request $request, Leave $leave, User $user)
    {
        if (!($user->hasRole('manager') || $user->hasRole('hrd'))) {
            return view('leave.action-response', [
                'title' => 'Tidak Diizinkan',
                'message' => 'Anda tidak memiliki izin untuk melakukan tindakan ini.',
                'status' => 'error'
            ]);
        }
        
        // Update leave status
        if ($user->hasRole('manager')) {
            $leave->approval_manager = false;
            $leave->rejection_reason = 'Ditolak oleh manager melalui email';
        } else {
            $leave->approval_hrd = false;
            $leave->rejection_reason = 'Ditolak oleh HRD melalui email';
        }
        
        $leave->status = 'rejected';
        $leave->save();
        
        // If leave was a casual leave, refund the quota
        if ($leave->leave_type === 'casual') {
            $quota = $leave->user->getCurrentYearQuota();
            $quota->casual_used = max(0, $quota->casual_used - 1);
            $quota->save();
        }
        
        // Send notification to employee
        $this->notifyStatusChange($leave);
        
        return view('leave.action-response', [
            'title' => 'Permintaan Cuti Ditolak',
            'message' => 'Anda telah menolak permintaan cuti dari ' . $leave->user->name,
            'status' => 'warning'
        ]);
    }
    
    private function notifyStatusChange(Leave $leave)
    {
        $leave->user->notify(new LeaveStatusUpdated($leave));
    }
}