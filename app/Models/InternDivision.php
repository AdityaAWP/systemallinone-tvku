<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InternDivision extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    public function interns(): HasMany
    {
        return $this->hasMany(Intern::class, 'intern_division_id');
    }
}