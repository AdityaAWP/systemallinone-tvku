<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InternSchool extends Model
{
    protected $fillable = [
        'name',
        'type',
    ];

    public function interns(): HasMany
    {
        return $this->hasMany(Intern::class, 'school_id');
    }
}