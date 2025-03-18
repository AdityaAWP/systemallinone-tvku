<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DailyReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'entry_date',
        'check_in',
        'check_out',
        'work_hours',
        'work_hours_component',
        'work_minutes_component',
        'description',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'check_in' => 'datetime',
        'check_out' => 'datetime',
        'work_hours' => 'decimal:2',
        'work_hours_component' => 'integer',
        'work_minutes_component' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            if ($model->check_in && $model->check_out) {
                try {
                    $tanggalString = Carbon::parse($model->entry_date)->format('Y-m-d');

                    $checkInTime = Carbon::parse($model->check_in)->format('H:i:s');
                    $checkOutTime = Carbon::parse($model->check_out)->format('H:i:s');

                    $checkInDateTime = Carbon::parse($tanggalString . ' ' . $checkInTime);
                    $checkOutDateTime = Carbon::parse($tanggalString . ' ' . $checkOutTime);

                    if ($checkOutDateTime->lt($checkInDateTime)) {
                        $checkOutDateTime->addDay();
                    }

                    $totalMinutes = abs($checkOutDateTime->diffInMinutes($checkInDateTime));

                    $model->work_hours = round($totalMinutes / 60, 2);
                    $model->work_hours_component = (int)floor($totalMinutes / 60);
                    $model->work_minutes_component = $totalMinutes % 60;

                    Log::info("Model saving - Check-in: {$checkInDateTime->format('Y-m-d H:i:s')}, Check-out: {$checkOutDateTime->format('Y-m-d H:i:s')}, Total minutes: {$totalMinutes}, Hours: {$model->work_hours_component}, Minutes: {$model->work_minutes_component}");
                } catch (\Exception $e) {
                    Log::error("Error in boot saving: " . $e->getMessage() . " at line " . $e->getLine());
                }
            }
        });
    }

    public function getOvertimeFormattedAttribute()
    {
        return $this->work_hours_component . ' jam ' . $this->work_minutes_component . ' menit';
    }
}
