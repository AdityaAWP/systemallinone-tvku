<?php

namespace App\Providers;

use App\Models\FinancialAssignmentLetter;
use App\Models\IncomingLetter;
use App\Models\OutgoingLetter;
use App\Models\LetterAttachment;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        // Handle saving attachments for Financial Assignment Letters
        FinancialAssignmentLetter::created(function (FinancialAssignmentLetter $letter) {
            $this->saveAttachments($letter);
        });
        
        FinancialAssignmentLetter::updated(function (FinancialAssignmentLetter $letter) {
            $this->saveAttachments($letter);
        });
        
        // Handle saving attachments for Incoming Letters
        IncomingLetter::created(function (IncomingLetter $letter) {
            $this->saveAttachments($letter);
        });
        
        IncomingLetter::updated(function (IncomingLetter $letter) {
            $this->saveAttachments($letter);
        });
        
        // Handle saving attachments for Outgoing Letters
        OutgoingLetter::created(function (OutgoingLetter $letter) {
            $this->saveAttachments($letter);
        });
        
        OutgoingLetter::updated(function (OutgoingLetter $letter) {
            $this->saveAttachments($letter);
        });
    }
    
    /**
     * Save attachments from form data to the model
     */
    private function saveAttachments($model): void
    {
        if (request()->has('attachments')) {
            foreach (request()->input('attachments') as $attachment) {
                if (!isset($attachment['path'])) {
                    continue;
                }
                
                $model->attachments()->create([
                    'filename' => $attachment['filename'],
                    'path' => $attachment['path'],
                    'mime_type' => $attachment['mime_type'],
                    'size' => $attachment['size'],
                ]);
            }
        }
    }
}