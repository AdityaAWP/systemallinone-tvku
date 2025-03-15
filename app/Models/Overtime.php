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
        'check_in',
        'check_out',
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
                    // Ambil tanggal dari tanggal_overtime
                    $tanggalString = Carbon::parse($model->tanggal_overtime)->format('Y-m-d');
                    
                    // Ambil waktu dari check_in dan check_out
                    $checkInTime = Carbon::parse($model->check_in)->format('H:i:s');
                    $checkOutTime = Carbon::parse($model->check_out)->format('H:i:s');
                    
                    // Gabungkan tanggal dengan waktu
                    $checkInDateTime = Carbon::parse($tanggalString . ' ' . $checkInTime);
                    $checkOutDateTime = Carbon::parse($tanggalString . ' ' . $checkOutTime);
                    
                    // Jika check-out lebih awal dari check-in, tambahkan 1 hari (untuk lembur yang melewati tengah malam)
                    if ($checkOutDateTime->lt($checkInDateTime)) {
                        $checkOutDateTime->addDay();
                    }
                    
                    // Hitung selisih dalam menit dan pastikan positif dengan menggunakan abs()
                    $totalMinutes = abs($checkOutDateTime->diffInMinutes($checkInDateTime));
                    
                    // Set nilai ke model
                    $model->overtime = round($totalMinutes / 60, 2);
                    $model->overtime_hours = (int)floor($totalMinutes / 60);
                    $model->overtime_minutes = $totalMinutes % 60;
                    
                    // Debug log
                    Log::info("Model saving - Check-in: {$checkInDateTime->format('Y-m-d H:i:s')}, Check-out: {$checkOutDateTime->format('Y-m-d H:i:s')}, Total minutes: {$totalMinutes}, Hours: {$model->overtime_hours}, Minutes: {$model->overtime_minutes}");
                } catch (\Exception $e) {
                    Log::error("Error in boot saving: " . $e->getMessage() . " at line " . $e->getLine());
                }
            }
        });
    }
    
    // Method untuk mendapatkan format overtime dalam jam dan menit
    public function getOvertimeFormattedAttribute()
    {
        return $this->overtime_hours . ' jam ' . $this->overtime_minutes . ' menit';
    }
}