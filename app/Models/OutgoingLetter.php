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
        'recipient',
        'subject',
        'content',
        'letter_date',
        'notes'
    ];

    protected $casts = [
        'letter_date' => 'date',
    ];

    public function attachments()
    {
        return $this->morphMany(LetterAttachment::class, 'attachable');
    }
    
    public static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            $prefix = 'O-';
            $lastLetter = self::latest('id')->first();
            $nextNumber = $lastLetter ? intval(substr($lastLetter->reference_number, 2)) + 1 : 1;
            $model->reference_number = $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        });
    }
}