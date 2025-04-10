<?php

namespace App\Console\Commands;

use App\Jobs\SendLeaveReminderEmailJob;
use App\Models\User;
use Illuminate\Console\Command;

class SendLeaveReminderEmails extends Command
{
    protected $signature = 'leaves:send-reminder';
    protected $description = 'Send reminder emails to managers about staff leaves';

    public function handle()
    {
        $managers = User::role('manager')->get();
        
        foreach ($managers as $manager) {
            SendLeaveReminderEmailJob::dispatch($manager);
        }
        
        $this->info('Leave reminder emails have been dispatched successfully.');
        
        return Command::SUCCESS;
    }
}