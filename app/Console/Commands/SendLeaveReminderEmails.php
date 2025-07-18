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
        // Kirim reminder ke semua manager dan kepala
        $managers = User::whereHas('roles', function ($query) {
            $query->where('name', 'like', 'manager_%')
                  ->orWhere('name', 'like', 'kepala_%');
        })
        ->where('is_active', true)
        ->get();
        
        foreach ($managers as $manager) {
            SendLeaveReminderEmailJob::dispatch($manager);
        }
        
        $this->info('Leave reminder emails have been dispatched successfully to ' . $managers->count() . ' managers/kepala.');
        
        return Command::SUCCESS;
    }
}