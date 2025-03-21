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
            ->subject('New Leave Request from ' . $this->leave->user->name)
            ->greeting('Hello ' . $notifiable->name)
            ->line($this->leave->user->name . ' has requested ' . $this->leave->leave_type . ' leave.')
            ->line('From: ' . $this->leave->from_date->format('d M Y') . ' To: ' . $this->leave->to_date->format('d M Y'))
            ->line('Reason: ' . $this->leave->reason)
            ->action('View Request', $url)
            ->line('Please review this request at your earliest convenience.');
    }
}