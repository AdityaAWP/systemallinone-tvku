<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'avatar',
        'is_admin',
        'created_by',
        'position_id',
        'gender',
        'ktp',
        'address',
        'birth',
        'last_education',
        'phone',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_admin' => 'boolean',
        'birth' => 'date',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin || $this->position?->role === 'user';
    }

    public function createdUsers()
    {
        return $this->hasMany(User::class, 'created_by');
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * Check if the user is a Super Admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->position?->role === 'super_admin';
    }
    
    /**
     * Check if the user is an Admin.
     */
    public function isAdmin(): bool
    {
        return $this->is_admin && $this->position?->role === 'admin';
    }
}