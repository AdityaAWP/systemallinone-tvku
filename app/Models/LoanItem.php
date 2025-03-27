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
        'program',
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
        return $this->belongsToMany(Item::class, 'loan_item_pivot')
            ->withPivot('quantity')
            ->withTimestamps();
    }
    
    // Add this method to handle stock changes when loan is approved
    public function processStockChanges($oldStatus, $newStatus)
    {
        if ($oldStatus === $newStatus) {
            return;
        }
        
        // If status changed to Approve, decrease stock
        if ($newStatus === 'Approve' && $oldStatus !== 'Approve') {
            foreach ($this->items as $item) {
                $item->decreaseStock($item->pivot->quantity);
            }
        }
        
        // If status changed from Approve to something else, restore stock
        if ($oldStatus === 'Approve' && $newStatus !== 'Approve') {
            foreach ($this->items as $item) {
                $item->increaseStock($item->pivot->quantity);
            }
        }
    }
    
    // Add this method to handle return status changes
    public function processReturnStatusChanges($oldStatus, $newStatus)
    {
        if ($oldStatus === $newStatus) {
            return;
        }
        
        // If status changed to "Sudah Dikembalikan", increase stock
        if ($newStatus === 'Sudah Dikembalikan' && $oldStatus !== 'Sudah Dikembalikan') {
            foreach ($this->items as $item) {
                $item->increaseStock($item->pivot->quantity);
            }
        }
        
        // If status changed from "Sudah Dikembalikan" to "Belum Dikembalikan", decrease stock
        if ($oldStatus === 'Sudah Dikembalikan' && $newStatus === 'Belum Dikembalikan') {
            foreach ($this->items as $item) {
                $item->decreaseStock($item->pivot->quantity);
            }
        }
    }
}