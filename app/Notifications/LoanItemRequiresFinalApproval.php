<?php

namespace App\Notifications;

use App\Models\LoanItem;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification as BaseNotification;

class LoanItemRequiresFinalApproval extends BaseNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(public LoanItem $loanItem)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return Notification::make()
            ->title('Loan Item Requires Final Approval')
            ->body("A loan item for program {$this->loanItem->program} has been approved by logistics and requires your final approval.")
            ->getDatabaseMessage();
    }

    public function toArray($notifiable): array
    {
        return [
            //
        ];
    }
}