<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;

class TestEmail extends Command
{
    protected $signature = 'mail:test {email}';
    protected $description = 'Test email configuration';

    public function handle()
    {
        $email = $this->argument('email');
        $this->info("Sending test email to {$email}");
        
        try {
            Mail::raw('Test email from Laravel', function (Message $message) use ($email) {
                $message->to($email)
                    ->subject('Test Email');
            });
            
            $this->info("Email sent successfully!");
        } catch (\Exception $e) {
            $this->error("Failed to send email: " . $e->getMessage());
        }
        
        return 0;
    }
}