<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class Overtime extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'tanggal_overtime',
        'is_holiday',
        'check_in',
        'check_out',
        'normal_work_time_check_in',
        'normal_work_time_check_out',
        'overtime',
        'overtime_hours',
        'overtime_minutes',
        'description',
    ];

    protected $casts = [
        'tanggal_overtime' => 'date',
        'check_in' => 'datetime',
        'check_out' => 'datetime',
        'overtime' => 'decimal:2',
        'is_holiday' => 'boolean',
        'overtime_hours' => 'integer',
        'overtime_minutes' => 'integer',
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
                    $tanggalString = Carbon::parse($model->tanggal_overtime)->format('Y-m-d');

                    $checkInTime = Carbon::parse($model->check_in)->format('H:i:s');
                    $checkOutTime = Carbon::parse($model->check_out)->format('H:i:s');

                    $checkInDateTime = Carbon::parse($tanggalString . ' ' . $checkInTime);
                    $checkOutDateTime = Carbon::parse($tanggalString . ' ' . $checkOutTime);

                    if ($checkOutDateTime->lt($checkInDateTime)) {
                        $checkOutDateTime->addDay();
                    }

                    $totalMinutes = abs($checkOutDateTime->diffInMinutes($checkInDateTime));

                    $model->overtime = round($totalMinutes / 60, 2);
                    $model->overtime_hours = (int)floor($totalMinutes / 60);
                    $model->overtime_minutes = $totalMinutes % 60;

                    Log::info("Model saving - Check-in: {$checkInDateTime->format('Y-m-d H:i:s')}, Check-out: {$checkOutDateTime->format('Y-m-d H:i:s')}, Total minutes: {$totalMinutes}, Hours: {$model->overtime_hours}, Minutes: {$model->overtime_minutes}");
                } catch (\Exception $e) {
                    Log::error("Error in boot saving: " . $e->getMessage() . " at line " . $e->getLine());
                }
            }
        });
    }

    public function getOvertimeFormattedAttribute()
    {
        return $this->overtime_hours . ' jam ' . $this->overtime_minutes . ' menit';
    }
}
