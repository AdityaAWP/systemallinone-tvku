<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancialAssignmentLetter extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reference_number',
        'title',
        'content',
        'letter_date',
        'created_by',
        'manager_approval',
        'manager_approval_date',
        'director_approval',
        'director_approval_date',
        'status',
        'notes'
    ];

    protected $casts = [
        'letter_date' => 'date',
        'manager_approval' => 'boolean',
        'manager_approval_date' => 'datetime',
        'director_approval' => 'boolean',
        'director_approval_date' => 'datetime',
    ];

    public function attachments()
    {
        return $this->morphMany(LetterAttachment::class, 'attachable');
    }
}