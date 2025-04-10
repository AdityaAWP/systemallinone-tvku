<?php

namespace App\Jobs;

use App\Models\Leave;
use App\Models\User;
use App\Notifications\LeaveManagerReminder;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendLeaveReminderEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $manager;

    public function __construct(User $manager)
    {
        $this->manager = $manager;
    }

    public function handle()
    {
        // Get staff members in the same division as the manager
        $staffIds = User::where('division_id', $this->manager->division_id)
            ->pluck('id')
            ->toArray();
        
        // Skip if no staff
        if (empty($staffIds)) {
            return;
        }
        
        // Get pending leave requests
        $pendingLeaves = Leave::whereIn('user_id', $staffIds)
            ->where('status', 'pending')
            ->get();
        
        // Get upcoming approved leaves (next 7 days)
        $upcomingLeaves = Leave::whereIn('user_id', $staffIds)
            ->where('status', 'approved')
            ->where('from_date', '>=', Carbon::now())
            ->where('from_date', '<=', Carbon::now()->addDays(7))
            ->get();
        
        // Get staff with low remaining quota
        $staffWithLowQuota = [];
        foreach ($staffIds as $staffId) {
            $staff = User::find($staffId);
            if (!$staff || $staff->id === $this->manager->id) continue;
            
            $quota = $staff->getCurrentYearQuota();
            $remainingQuota = ($quota->casual_quota ?? 12) - ($quota->casual_used ?? 0);
            
            if ($remainingQuota <= 3) {
                $staffWithLowQuota[] = [
                    'name' => $staff->name,
                    'remaining' => $remainingQuota
                ];
            }
        }
        
        // Only send notification if there's something to report
        if ($pendingLeaves->count() > 0 || $upcomingLeaves->count() > 0 || count($staffWithLowQuota) > 0) {
            $this->manager->notify(new LeaveManagerReminder($pendingLeaves, $upcomingLeaves, $staffWithLowQuota));
        }
    }
}