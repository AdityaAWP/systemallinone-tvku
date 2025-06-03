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
        'no_phone', // ganti dari 'phone' ke 'no_phone'
        'npp',      // pastikan 'npp' ada
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
    // Di model User.php
public function getAtasanAttribute()
{
    // Cari user dengan role manager atau kepala divisi yang sama
    $divisionId = $this->division_id;
    
    // Cari manager divisi terlebih dahulu
    $manager = User::whereHas('roles', function($query) {
            $query->where('name', 'like', 'manager_%');
        })
        ->where('division_id', $divisionId)
        ->first();
    
    // Jika tidak ada manager, cari kepala divisi
    if (!$manager) {
        $manager = User::whereHas('roles', function($query) use ($divisionId) {
                $query->where('name', 'like', 'kepala_%')
                     ->orWhere('name', 'like', 'head_%');
            })
            ->where('division_id', $divisionId)
            ->first();
    }
    
    return $manager;
}

public function getJabatanAtasanAttribute()
{
    $atasan = $this->atasan;
    if (!$atasan) return null;
    
    foreach ($atasan->roles as $role) {
        if (str_contains($role->name, 'manager')) {
            return 'Manager ' . str_replace('manager_', '', $role->name);
        } elseif (str_contains($role->name, 'kepala') || str_contains($role->name, 'head')) {
            return 'Kepala ' . str_replace(['kepala_', 'head_'], '', $role->name);
        }
    }
    
    return 'Atasan';
}
}