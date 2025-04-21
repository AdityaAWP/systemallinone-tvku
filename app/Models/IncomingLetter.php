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
        'subject',
        'content',
        'letter_date',
        'received_date',
        'notes'
    ];

    protected $casts = [
        'letter_date' => 'date',
        'received_date' => 'date',
    ];

    public function attachments()
    {
        return $this->morphMany(LetterAttachment::class, 'attachable');
    }
    
    public static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            $prefix = ($model->type == 'internal') ? 'I-' : 'U-';
            $lastLetter = self::where('type', $model->type)->latest('id')->first();
            $nextNumber = $lastLetter ? intval(substr($lastLetter->reference_number, 2)) + 1 : 1;
            $model->reference_number = $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        });
    }
}