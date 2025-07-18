<?php

namespace App\Notifications;

use App\Models\Leave;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class LeaveRequested extends Notification implements ShouldQueue
{
    use Queueable;

    protected $leave;

    public function __construct(Leave $leave)
    {
        $this->leave = $leave;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $baseUrl = config('app.url');

        // Perbaiki penentuan role: cek jika user memiliki role manager_ atau kepala_
        $isManager = $notifiable->roles()->where('name', 'like', 'manager%')->exists();
        $isKepala = $notifiable->roles()->where('name', 'like', 'kepala%')->exists();
        
        Log::info('Debugging role detection untuk ' . $notifiable->email . ':');
        Log::info('- isManager: ' . ($isManager ? 'true' : 'false'));
        Log::info('- isKepala: ' . ($isKepala ? 'true' : 'false'));
        Log::info('- All roles: ' . $notifiable->roles()->pluck('name')->implode(', '));
        
        $role = ($isManager || $isKepala) ? 'manager' : 'hrd';

        $approveUrl = $baseUrl . '/leave/approve-by-token/' . $this->leave->approval_token . '?role=' . $role;
        $rejectUrl = $baseUrl . '/leave/reject-by-token/' . $this->leave->approval_token . '?role=' . $role;

        $leaveType = $this->translateLeaveType($this->leave->leave_type);

        Log::info('Mengirim email permintaan cuti ke: ' . $notifiable->email . ' sebagai ' . $role);
        Log::info('Link approve: ' . $approveUrl);
        Log::info('Link reject: ' . $rejectUrl);
        Log::info('Role yang terdeteksi untuk ' . $notifiable->name . ' (' . $notifiable->email . '): ' . $role);
        Log::info('Roles user: ' . $notifiable->roles()->pluck('name')->implode(', '));

        // Gunakan view kustom modern dengan Tailwind dan font Poppins
        return (new MailMessage)
            ->subject('✉️ Permintaan Cuti dari ' . $this->leave->user->name)
            ->view(
                'emails.leave-request', 
                [
                    'name' => $notifiable->name,
                    'requester' => $this->leave->user->name,
                    'days' => $this->leave->days,
                    'leaveType' => $leaveType,
                    'fromDate' => $this->leave->from_date->format('d M Y'),
                    'toDate' => $this->leave->to_date->format('d M Y'),
                    'reason' => $this->leave->reason,
                    'approveUrl' => $approveUrl,
                    'rejectUrl' => $rejectUrl
                ]
            );
    }
    
    public function toDatabase($notifiable)
    {
        return [
            'leave_id' => $this->leave->id,
            'user_name' => $this->leave->user->name,
            'leave_type' => $this->leave->leave_type,
            'from_date' => $this->leave->from_date->format('d M Y'),
            'to_date' => $this->leave->to_date->format('d M Y'),
            'message' => $this->leave->user->name . ' telah mengajukan cuti ' . $this->translateLeaveType($this->leave->leave_type),
        ];
    }

    private function translateLeaveType($type)
    {
        $translations = [
            'casual' => 'Tahunan',
            'medical' => 'Sakit',
            'maternity' => 'Melahirkan',
            'other' => 'Lainnya'
        ];

        return $translations[$type] ?? $type;
    }
}