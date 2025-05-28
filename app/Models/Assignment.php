<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Assignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'client',
        'spp_number',
        'spk_number',
        'description',
        'amount',
        'marketing_expense',
        'deadline',
        'production_notes',
        'type',
        'priority',
        'approval_status',
        'approved_by',
        'approved_at',
        'created_date',
        'created_by',
    ];

    protected $casts = [
        'deadline' => 'date',
        'amount' => 'decimal:2',
        'marketing_expense' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    // Possible types
    const TYPE_FREE = 'free';
    const TYPE_PAID = 'paid';
    const TYPE_BARTER = 'barter';

    // Possible priorities
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_IMPORTANT = 'important';
    const PRIORITY_VERY_IMPORTANT = 'very_important';

    // Possible approval statuses
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_DECLINED = 'declined';

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
    
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isPending(): bool
    {
        return $this->approval_status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->approval_status === self::STATUS_APPROVED;
    }

    public function isDeclined(): bool
    {
        return $this->approval_status === self::STATUS_DECLINED;
    }

    public function isFree(): bool
    {
        return $this->type === self::TYPE_FREE;
    }

    public function isPaid(): bool
    {
        return $this->type === self::TYPE_PAID;
    }

    public function isBarter(): bool
    {
        return $this->type === self::TYPE_BARTER;
    }
}