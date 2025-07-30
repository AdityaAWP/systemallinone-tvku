<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Assignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'client',
        'spp_number',
        'spk_number',
        'description',
        'amount',
        'marketing_expense',
        'production_notes',
        'priority',
        'created_date',
        'deadline',
        'approval_status',
        'approved_by',
        'approved_at',
        'created_by',
        'submit_status',
        'submitted_by',
        'submitted_at',
    ];

    protected $casts = [
        'created_date' => 'date',
        'deadline' => 'date',
        'amount' => 'decimal:2',
        'marketing_expense' => 'decimal:2',
        'approved_at' => 'datetime',
        'submitted_at' => 'datetime',
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
    public const STATUS_SUBMITTED = 'submitted';
    const STATUS_REJECTED = 'rejected';

    // Possible submit statuses
    const SUBMIT_BELUM = 'belum';
    const SUBMIT_SUDAH = 'sudah';

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
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

    public function isSubmitted(): bool
    {
        return $this->submit_status === self::SUBMIT_SUDAH;
    }

    public function isNotSubmitted(): bool
    {
        return $this->submit_status === self::SUBMIT_BELUM;
    }

    /**
     * Get the net amount (amount - marketing_expense)
     */
    public function getNetAmountAttribute(): ?float
    {
        // Return null for free assignments
        if ($this->type === self::TYPE_FREE) {
            return null;
        }

        $amount = $this->amount ?? 0;
        $marketingExpense = $this->marketing_expense ?? 0;
        
        return $amount - $marketingExpense;
    }
}