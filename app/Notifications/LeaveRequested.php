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

        // Check if notifiable is director
        $isDirector = $notifiable->roles()->whereIn('name', ['direktur_utama', 'direktur_operasional'])->exists();
        
        // Perbaiki penentuan role: cek jika user memiliki role manager_ atau kepala_
        $isManager = $notifiable->roles()->where('name', 'like', 'manager%')->exists();
        $isKepala = $notifiable->roles()->where('name', 'like', 'kepala%')->exists();
        
        Log::info('Debugging role detection untuk ' . $notifiable->email . ':');
        Log::info('- isDirector: ' . ($isDirector ? 'true' : 'false'));
        Log::info('- isManager: ' . ($isManager ? 'true' : 'false'));
        Log::info('- isKepala: ' . ($isKepala ? 'true' : 'false'));
        Log::info('- All roles: ' . $notifiable->roles()->pluck('name')->implode(', '));
        
        $leaveType = $this->translateLeaveType($this->leave->leave_type);

        // For directors, send information-only email without approval buttons
        if ($isDirector) {
            Log::info('Mengirim email informasi cuti ke direktur: ' . $notifiable->email);
            
            return (new MailMessage)
                ->subject('📋 Informasi Cuti Manager/Kepala - ' . $this->leave->user->name)
                ->view(
                    'emails.leave-info-director', 
                    [
                        'name' => $notifiable->name,
                        'requester' => $this->leave->user->name,
                        'days' => $this->leave->days,
                        'leaveType' => $leaveType,
                        'fromDate' => $this->leave->from_date->format('d M Y'),
                        'toDate' => $this->leave->to_date->format('d M Y'),
                        'reason' => $this->leave->reason,
                        'status' => 'Approved (Auto-approved for Manager/Kepala)'
                    ]
                );
        }
        
        // Original logic for manager/kepala/hrd approval
        $role = ($isManager || $isKepala) ? 'manager' : 'hrd';

        $approveUrl = $baseUrl . '/leave/approve-by-token/' . $this->leave->approval_token . '?role=' . $role;
        $rejectUrl = $baseUrl . '/leave/reject-by-token/' . $this->leave->approval_token . '?role=' . $role;

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
        // Check if notifiable is director
        $isDirector = $notifiable->roles()->whereIn('name', ['direktur_utama', 'direktur_operasional'])->exists();
        
        if ($isDirector) {
            return [
                'leave_id' => $this->leave->id,
                'user_name' => $this->leave->user->name,
                'leave_type' => $this->leave->leave_type,
                'from_date' => $this->leave->from_date->format('d M Y'),
                'to_date' => $this->leave->to_date->format('d M Y'),
                'message' => '[INFO] Manager/Kepala ' . $this->leave->user->name . ' telah mengajukan cuti ' . $this->translateLeaveType($this->leave->leave_type) . ' (Auto-approved)',
                'is_info_only' => true,
            ];
        }
        
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