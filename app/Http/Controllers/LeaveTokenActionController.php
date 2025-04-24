<?php

namespace App\Http\Controllers;

use App\Models\Leave;
use App\Models\LeaveQuota;
use App\Notifications\LeaveStatusUpdated;
use Illuminate\Http\Request;

class LeaveTokenActionController extends Controller
{
    public function approve($token)
    {
        $leave = Leave::where('approval_token', $token)->first();

        if (!$leave) {
            return view('leave.action-response', [
                'title' => 'Token Tidak Valid',
                'message' => 'Permintaan cuti tidak ditemukan atau sudah diproses.',
                'status' => 'error'
            ]);
        }

        $leave->approval_hrd = true;

        // Jika Manager sudah approve atau tidak perlu approval manager
        if ($leave->approval_manager === true || $leave->approval_manager === null) {
            $leave->status = 'approved';
        }

        // Invalidasi token setelah digunakan
        $leave->approval_token = null;
        $leave->save();

        // Kirim notifikasi ke staff
        $leave->user->notify(new LeaveStatusUpdated($leave));

        return view('leave.action-response', [
            'title' => 'Permintaan Cuti Disetujui',
            'message' => 'Anda telah menyetujui permintaan cuti dari ' . $leave->user->name,
            'status' => 'success'
        ]);
    }

    public function reject($token)
    {
        $leave = Leave::where('approval_token', $token)->first();

        if (!$leave) {
            return view('leave.action-response', [
                'title' => 'Token Tidak Valid',
                'message' => 'Permintaan cuti tidak ditemukan atau sudah diproses.',
                'status' => 'error'
            ]);
        }

        $leave->approval_hrd = false;
        $leave->status = 'rejected';
        $leave->rejection_reason = 'Ditolak oleh HRD melalui email';

        // Invalidasi token setelah digunakan
        $leave->approval_token = null;
        $leave->save();

        // Jika cuti casual, kembalikan quota
        if ($leave->leave_type === 'casual') {
            $quota = $leave->user->getCurrentYearQuota();
            $quota->casual_used = max(0, $quota->casual_used - 1);
            $quota->save();
        }

        // Kirim notifikasi ke staff
        $leave->user->notify(new LeaveStatusUpdated($leave));

        return view('leave.action-response', [
            'title' => 'Permintaan Cuti Ditolak',
            'message' => 'Anda telah menolak permintaan cuti dari ' . $leave->user->name,
            'status' => 'warning'
        ]);
    }
}
