<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoanItem extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id',
        'location',
        'booking_date',
        'start_booking',
        'return_date',
        'producer_name',
        'producer_telp',
        'crew_name',
        'crew_telp',
        'crew_division',
        'approver_name',
        'approver_telp',
        'approval_status',
        'return_status',
        'description',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'return_date' => 'date',
        'start_booking' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Item::class, 'loan_item_equipment')
            ->withPivot('quantity')
            ->withTimestamps();
    }
}