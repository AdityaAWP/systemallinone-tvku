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
        'division',
        'booking_date',
        'start_booking',
        'return_date',
        'producer_name',
        'producer_telp',
        'crew_name',
        'crew_telp',
        'crew_division',
        'approval_admin_logistics',
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
        'approval_admin_logistics' => 'boolean',
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
    
    public function processStockChanges($oldStatus, $newStatus)
    {
        if ($oldStatus === $newStatus) {
            return;
        }
        
        if ($newStatus === 'Approve' && $oldStatus !== 'Approve') {
            foreach ($this->items as $item) {
                $item->decreaseStock($item->pivot->quantity);
            }
        }
        
        if ($oldStatus === 'Approve' && $newStatus !== 'Approve') {
            foreach ($this->items as $item) {
                $item->increaseStock($item->pivot->quantity);
            }
        }
    }
    
    public function processReturnStatusChanges($oldStatus, $newStatus)
    {
        if ($oldStatus === $newStatus) {
            return;
        }
        
        if ($newStatus === 'Sudah Dikembalikan' && $oldStatus !== 'Sudah Dikembalikan') {
            foreach ($this->items as $item) {
                $item->increaseStock($item->pivot->quantity);
            }
        }
        
        if ($oldStatus === 'Sudah Dikembalikan' && $newStatus === 'Belum Dikembalikan') {
            foreach ($this->items as $item) {
                $item->decreaseStock($item->pivot->quantity);
            }
        }
    }
    
    public function notifyLogisticsAdmin()
    {
        $logisticsAdmins = User::whereHas('roles', function($query) {
            $query->where('name', 'admin_logistics');
        })->get();
        
        foreach ($logisticsAdmins as $admin) {
            $admin->notify(new \App\Notifications\LoanItemRequiresLogisticsApproval($this));
        }
    }
}