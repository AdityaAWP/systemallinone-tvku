<?php

namespace App\Notifications;

use App\Models\Leave;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

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
        // Generate signed URLs for approve/reject actions
        $approveUrl = URL::temporarySignedRoute(
            'leave.approve',
            now()->addDays(7),
            [
                'leave' => $this->leave->id,
                'user' => $notifiable->id,
                'action' => 'approve'
            ]
        );
        
        $rejectUrl = URL::temporarySignedRoute(
            'leave.reject',
            now()->addDays(7),
            [
                'leave' => $this->leave->id,
                'user' => $notifiable->id,
                'action' => 'reject'
            ]
        );

        $leaveType = $this->translateLeaveType($this->leave->leave_type);
        
        return (new MailMessage)
            ->subject('Permintaan Cuti Baru dari ' . $this->leave->user->name)
            ->greeting('Halo ' . $notifiable->name)
            ->line($this->leave->user->name . ' telah mengajukan ' . $this->leave->days . ' hari cuti ' . $leaveType . '.')
            ->line('Dari: ' . $this->leave->from_date->format('d M Y') . ' Sampai: ' . $this->leave->to_date->format('d M Y'))
            ->line('Alasan: ' . $this->leave->reason)
            ->action('Lihat Detail', route('filament.admin.resources.leaves.edit', $this->leave->id))
            ->line('Atau Anda dapat langsung menyetujui/menolak dari email ini:')
            ->line('<div style="text-align: center; margin: 20px 0;">
                <a href="'.$approveUrl.'" style="background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; margin-right: 10px; border-radius: 4px;">Setujui</a>
                <a href="'.$rejectUrl.'" style="background-color: #f44336; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">Tolak</a>
            </div>')
            ->line('Mohon tinjau permintaan ini secepatnya.');
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