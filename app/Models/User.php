<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable //implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'avatar',
        'is_admin',
        'created_by',
        'gender',
        'ktp',
        'address',
        'birth',
        'last_education',
        'phone',
        'division_id', // Add this line
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

    public function createdUsers()
    {
        return $this->hasMany(User::class, 'created_by');
    }

    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class);
    }
    
    public function leaveQuotas(): HasMany
    {
        return $this->hasMany(LeaveQuota::class);
    }

    public function getCurrentYearQuota()
    {
        return LeaveQuota::getUserQuota($this->id, date('Y'));
    }

    public function hasReachedMonthlyLeaveLimit($month = null, $year = null)
    {
        $month = $month ?? date('m');
        $year = $year ?? date('Y');
        
        $leavesCount = $this->leaves()
                    ->where('leave_type', '!=', 'maternity')
                    ->whereMonth('from_date', $month)
                    ->whereYear('from_date', $year)
                    ->where('status', '!=', 'rejected')
                    ->count();
        
        return $leavesCount >= 2;
    }
}