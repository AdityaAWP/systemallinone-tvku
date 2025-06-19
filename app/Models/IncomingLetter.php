<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IncomingLetter extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reference_number',
        'type',
        'sender',
        'letter_number', // Kolom baru
        'subject',
        'content',
        'letter_date',
        'received_date',
        'notes',
        'attachments',
    ];

    protected $casts = [
        'letter_date' => 'date',
        'received_date' => 'date',
        'attachments' => 'array',
    ];

    public function attachments()
    {
        return $this->morphMany(LetterAttachment::class, 'attachable');
    }
    
    public static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            $prefix = match ($model->type) {
                'internal' => 'I-',
                'general' => 'U-',
                'visit' => 'KP-',
                default => 'X-'
            };
            
            $lastLetter = self::where('type', $model->type)->latest('id')->first();
            $nextNumber = $lastLetter ? intval(substr($lastLetter->reference_number, strlen($prefix))) + 1 : 1;
            $model->reference_number = $prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        });
    }
}