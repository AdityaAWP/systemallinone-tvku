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

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'avatar',
        'role',
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

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_admin' => 'boolean',
        'birth' => 'date',
    ];

    /**
     * Determine if the user can access the Filament panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Allow access if the user is an admin or a regular user
        return $this->is_admin || $this->role === 'user';
    }

    /**
     * Get the users created by this user.
     */
    public function createdUsers()
    {
        return $this->hasMany(User::class, 'created_by');
    }

    /**
     * Get the position of the user.
     */
    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * Check if user is a super admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->is_admin && $this->role === 'super_admin';
    }
    
    /**
     * Check if user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->is_admin && $this->role === 'admin';
    }
}