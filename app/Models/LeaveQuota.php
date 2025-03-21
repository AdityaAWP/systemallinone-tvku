<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveQuota extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'year',
        'casual_quota',
        'casual_used'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getRemainingCasualQuotaAttribute()
    {
        return $this->casual_quota - $this->casual_used;
    }

    public static function getUserQuota($userId, $year = null)
    {
        $year = $year ?? date('Y');
        
        $quota = self::where('user_id', $userId)
                    ->where('year', $year)
                    ->first();
        
        if (!$quota) {
            $quota = self::create([
                'user_id' => $userId,
                'year' => $year,
                'casual_quota' => 12,
                'casual_used' => 0
            ]);
        }
        
        return $quota;
    }
}