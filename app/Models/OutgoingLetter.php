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
        'notes'
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $prefix = $model->type == 'internal' ? 'I-' : 'U-';
            $lastLetter = self::where('type', $model->type)->latest('id')->first();
            $nextNumber = $lastLetter ? intval(substr($lastLetter->reference_number, 2)) + 1 : 1;
            $model->reference_number = $prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        });
    }

    protected $casts = [
        'letter_date' => 'date',
    ];

    public function attachments()
    {
        return $this->morphMany(LetterAttachment::class, 'attachable');
    }
}
