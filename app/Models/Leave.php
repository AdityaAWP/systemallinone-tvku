<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Leave extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'leave_type',
        'reason',
        'from_date',
        'to_date',
        'days',
        'approval_manager',
        'approval_hrd',
        'attachment',
        'status',
        'rejection_reason',
        'approval_token'
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date' => 'date',
        'approval_manager' => 'boolean',
        'approval_hrd' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($leave) {
            if ($leave->from_date && $leave->to_date) {
                $leave->days = abs(Carbon::parse($leave->to_date)->diffInDays(Carbon::parse($leave->from_date))) + 1;
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForMonth($query, $month, $year)
    {
        return $query->whereMonth('from_date', $month)
                    ->whereYear('from_date', $year);
    }

    public function scopePendingApproval($query)
    {
        return $query->where('status', 'pending');
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}