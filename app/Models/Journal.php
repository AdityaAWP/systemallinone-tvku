<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Journal extends Model
{
    use HasFactory;

    protected $fillable = [
        'intern_id',
        'entry_date',
        'start_time',
        'end_time',
        'activity',
        'status',
        'image',
        'reason_of_absence',
        'latitude',
        'longitude',
        'location_address',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];
    public function intern(): BelongsTo
    {
        return $this->belongsTo(Intern::class, 'intern_id');
    }
}