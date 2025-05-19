<?php
// app/Models/Intern.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Intern extends Model
{
    protected $fillable = [
        'name',
        'birth_date',
        'email',
        'school_id',
        'division',
        'nis_nim',
        'no_phone',
        'institution_supervisor',
        'college_supervisor',
        'college_supervisor_phone',
        'start_date',
        'end_date',
        'password',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(InternSchool::class, 'school_id');
    }
}