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

        // Cek parameter role dari URL
        $role = request()->query('role');
        Log::info('Parameter role yang diterima: ' . ($role ?? 'null'));
        Log::info('URL lengkap: ' . request()->fullUrl());

        if ($role == 'manager') {
            $leave->approval_manager = true;
            $approverRole = 'Manager';
            Log::info('Setting approval_manager = true untuk leave ID: ' . $leave->id);

            // Jika HRD sudah approve
            if ($leave->approval_hrd === true) {
                $leave->status = 'approved';
                Log::info('HRD sudah approve, setting status = approved');
            }
        } else {
            // Default ke HRD jika tidak disebutkan
            $leave->approval_hrd = true;
            $approverRole = 'HRD';
            Log::info('Setting approval_hrd = true untuk leave ID: ' . $leave->id);

            // Jika Manager sudah approve
            if ($leave->approval_manager === true) {
                $leave->status = 'approved';
                Log::info('Manager sudah approve, setting status = approved');
            }
        }

        // Simpan perubahan
        $leave->save();

        Log::info('Cuti dengan ID ' . $leave->id . ' disetujui oleh ' . $approverRole);
        Log::info('Status approval_manager: ' . ($leave->approval_manager ? 'true' : 'false'));
        Log::info('Status approval_hrd: ' . ($leave->approval_hrd ? 'true' : 'false'));
        Log::info('Status cuti: ' . $leave->status);

        // Kirim notifikasi ke staff jika status berubah menjadi approved
        if ($leave->status == 'approved') {
            try {
                $leave->user->notify(new LeaveStatusUpdated($leave));
                Log::info('Notifikasi persetujuan cuti terkirim ke staff: ' . $leave->user->email);
            } catch (\Exception $e) {
                Log::error('Gagal mengirim notifikasi ke staff: ' . $e->getMessage());
            }
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

        // Cek parameter role dari URL
        $role = request()->query('role');
        Log::info('Parameter role yang diterima: ' . ($role ?? 'null'));
        Log::info('URL lengkap: ' . request()->fullUrl());

        if ($role == 'manager') {
            $leave->approval_manager = false;
            $leave->rejection_reason = 'Ditolak oleh Manager melalui email';
            $rejecterRole = 'Manager';
            Log::info('Setting approval_manager = false untuk leave ID: ' . $leave->id);
        } else {
            // Default ke HRD jika tidak disebutkan
            $leave->approval_hrd = false;
            $leave->rejection_reason = 'Ditolak oleh HRD melalui email';
            $rejecterRole = 'HRD';
            Log::info('Setting approval_hrd = false untuk leave ID: ' . $leave->id);
        }

        $leave->status = 'rejected';

        // Simpan perubahan
        $leave->save();

        Log::info('Cuti dengan ID ' . $leave->id . ' ditolak oleh ' . $rejecterRole);

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
            Log::info('Notifikasi penolakan cuti terkirim ke staff: ' . $leave->user->email);
        } catch (\Exception $e) {
            Log::error('Gagal mengirim notifikasi penolakan: ' . $e->getMessage());
        }

        return view('leave.action-response', [
            'title' => 'Permintaan Cuti Ditolak',
            'message' => 'Anda telah menolak permintaan cuti dari ' . $leave->user->name,
            'status' => 'warning'
        ]);
    }
}
