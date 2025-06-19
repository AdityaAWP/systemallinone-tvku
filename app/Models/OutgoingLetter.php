<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OutgoingLetter extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reference_number',
        'type', 
        'recipient',
        'subject',
        'content',
        'letter_date',
        'notes',
        'attachments',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->reference_number) || $model->reference_number === 'auto') {
                $prefix = match ($model->type) {
                    'internal' => 'I-',
                    'general' => 'U-',
                    default => 'X-'
                };
                
                $lastLetter = self::where('type', $model->type)
                    ->where('reference_number', 'LIKE', $prefix . '%')
                    ->orderBy('reference_number', 'desc')
                    ->first();
                
                $nextNumber = 1;
                if ($lastLetter) {
                    $numberPart = substr($lastLetter->reference_number, strlen($prefix));
                    $nextNumber = intval($numberPart) + 1;
                }
                
                $model->reference_number = $prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            }
        });
        
        static::updating(function ($model) {
            // Jika tipe berubah, pastikan prefix nomor referensi sesuai
            if ($model->isDirty('type')) {
                $newPrefix = match ($model->type) {
                    'internal' => 'I-',
                    'general' => 'U-',
                    default => 'X-'
                };
                
                // Jika prefix tidak sesuai dengan tipe baru, generate ulang
                if (!str_starts_with($model->reference_number, $newPrefix)) {
                    $model->reference_number = self::generateNextReferenceNumber($model->type);
                }
            }
        });
    }
    
    /**
     * Generate next reference number for specific type
     */
    public static function generateNextReferenceNumber($type)
    {
        $prefix = match ($type) {
            'internal' => 'I-',
            'general' => 'U-',
            default => 'X-'
        };
        
        $lastLetter = self::where('type', $type)
            ->where('reference_number', 'LIKE', $prefix . '%')
            ->orderBy('reference_number', 'desc')
            ->first();
        
        $nextNumber = 1;
        if ($lastLetter) {
            $numberPart = substr($lastLetter->reference_number, strlen($prefix));
            $nextNumber = intval($numberPart) + 1;
        }
        
        return $prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    protected $casts = [
        'letter_date' => 'date',
        'attachments' => 'array',
    ];

    public function attachments()
    {
        return $this->morphMany(LetterAttachment::class, 'attachable');
    }
}
