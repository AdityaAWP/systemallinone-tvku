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

        $mailMessage = (new MailMessage)
            ->subject('Leave Request Status Updated')
            ->greeting('Hello ' . $notifiable->name)
            ->line('Your ' . $this->leave->leave_type . ' leave request for ' . $this->leave->from_date->format('d M Y') . ' to ' . $this->leave->to_date->format('d M Y') . ' has been ' . $this->leave->status . '.');
        
        if ($this->leave->isRejected() && $this->leave->rejection_reason) {
            $mailMessage->line('Reason for rejection: ' . $this->leave->rejection_reason);
        }
        
        return $mailMessage
            ->action('View Details', $url)
            ->line('Thank you for using our leave management system.');
    }
}