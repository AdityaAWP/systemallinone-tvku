<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Overtime extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'tanggal_overtime',
        'check_in',
        'check_out',
        'overtime',
        'description',
    ];

    protected $casts = [
        'tanggal_overtime' => 'date',
        'check_in' => 'datetime',
        'check_out' => 'datetime',
        'overtime' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($overtime) {
            if ($overtime->check_in && $overtime->check_out) {
                $checkIn = Carbon::parse($overtime->check_in);
                $checkOut = Carbon::parse($overtime->check_out);

                $totalHours = $checkOut->diffInMinutes($checkIn) / 60; 
                $overtime->overtime = max(0, $totalHours - 8); 
            }
        });
    }
}
