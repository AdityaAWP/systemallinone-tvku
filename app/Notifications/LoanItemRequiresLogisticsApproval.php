<?php

namespace App\Notifications;

use App\Models\LoanItem;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification as BaseNotification;

class LoanItemRequiresLogisticsApproval extends BaseNotification implements ShouldQueue
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
            ->title('New Loan Item Requires Approval')
            ->body("A new loan item for program {$this->loanItem->program} requires your approval.")
            ->getDatabaseMessage();
    }

    public function toArray($notifiable): array
    {
        return [
            //
        ];
    }
}