<?php

namespace App\Notifications;

use App\Models\Leave;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

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
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $url = route('filament.admin.resources.leaves.edit', $this->leave->id);

        return (new MailMessage)
            ->subject('Permintaan Cuti Baru dari ' . $this->leave->user->name)
            ->greeting('Halo ' . $notifiable->name)
            ->line($this->leave->user->name . ' telah mengajukan ' . $this->leave->days . ' hari cuti ' . $this->translateLeaveType($this->leave->leave_type) . '.')
            ->line('Dari: ' . $this->leave->from_date->format('d M Y') . ' Sampai: ' . $this->leave->to_date->format('d M Y'))
            ->line('Alasan: ' . $this->leave->reason)
            ->action('Tinjau Permintaan', $url)
            ->line('Mohon tinjau permintaan ini secepatnya.');
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