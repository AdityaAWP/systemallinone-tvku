<?php

namespace App\Notifications;

use App\Models\Leave;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    protected $leave;

    public function __construct(Leave $leave)
    {
        $this->leave = $leave;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $url = route('filament.admin.resources.leaves.view', $this->leave->id);

        $status = $this->translateStatus($this->leave->status);
        $leaveType = $this->translateLeaveType($this->leave->leave_type);
        
        $mailMessage = (new MailMessage)
            ->subject('Status Permintaan Cuti: ' . $status)
            ->greeting('Halo ' . $notifiable->name)
            ->line('Permintaan cuti ' . $leaveType . ' Anda dari ' . $this->leave->from_date->format('d M Y') . ' sampai ' . $this->leave->to_date->format('d M Y') . ' telah ' . $status . '.');
        
        if ($this->leave->isRejected() && $this->leave->rejection_reason) {
            $mailMessage->line('Alasan penolakan: ' . $this->leave->rejection_reason);
        }
        
        return $mailMessage
            ->action('Lihat Detail', $url)
            ->line('Terima kasih telah menggunakan sistem manajemen cuti kami.');
    }
    
    private function translateStatus($status)
    {
        $translations = [
            'pending' => 'Menunggu',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak'
        ];

        return $translations[$status] ?? $status;
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