<?php

namespace App\Http\Controllers;

use App\Models\Leave;
use App\Models\LeaveQuota;
use App\Notifications\LeaveStatusUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LeaveTokenActionController extends Controller
{
    public function approve($token)
    {
        Log::info('Mencoba menyetujui cuti dengan token: ' . $token);
        
        $leave = Leave::where('approval_token', $token)->first();

        if (!$leave) {
            Log::warning('Token cuti tidak valid: ' . $token);
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

        Log::info('Cuti dengan ID ' . $leave->id . ' disetujui oleh HRD');

        // Kirim notifikasi ke staff
        try {
            $leave->user->notify(new LeaveStatusUpdated($leave));
            Log::info('Notifikasi status cuti terkirim ke: ' . $leave->user->email);
        } catch (\Exception $e) {
            Log::error('Gagal mengirim notifikasi status cuti: ' . $e->getMessage());
        }

        return view('leave.action-response', [
            'title' => 'Permintaan Cuti Disetujui',
            'message' => 'Anda telah menyetujui permintaan cuti dari ' . $leave->user->name,
            'status' => 'success'
        ]);
    }

    public function reject($token)
    {
        Log::info('Mencoba menolak cuti dengan token: ' . $token);
        
        $leave = Leave::where('approval_token', $token)->first();

        if (!$leave) {
            Log::warning('Token cuti tidak valid: ' . $token);
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

        Log::info('Cuti dengan ID ' . $leave->id . ' ditolak oleh HRD');

        // Jika cuti casual, kembalikan quota
        if ($leave->leave_type === 'casual') {
            $quota = $leave->user->getCurrentYearQuota();
            if ($quota) {
                $quota->casual_used = max(0, $quota->casual_used - 1);
                $quota->save();
                Log::info('Quota cuti dikembalikan untuk user: ' . $leave->user->id);
            }
        }

        // Kirim notifikasi ke staff
        try {
            $leave->user->notify(new LeaveStatusUpdated($leave));
            Log::info('Notifikasi status cuti terkirim ke: ' . $leave->user->email);
        } catch (\Exception $e) {
            Log::error('Gagal mengirim notifikasi status cuti: ' . $e->getMessage());
        }

        return view('leave.action-response', [
            'title' => 'Permintaan Cuti Ditolak',
            'message' => 'Anda telah menolak permintaan cuti dari ' . $leave->user->name,
            'status' => 'warning'
        ]);
    }
}