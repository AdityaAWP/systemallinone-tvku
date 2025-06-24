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
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable //implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'position',
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
        'no_phone',
        'npp',
        'division_id',
        'manager_id', // Add this to fillable
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

    // Add this boot method to handle auto-population of division_id
    protected static function boot()
    {
        parent::boot();
        
        // When a user is saved, ensure division_id is set if divisions exist
        static::saved(function ($user) {
            // Only auto-set if division_id is null and user has divisions
            if (is_null($user->division_id) && $user->divisions()->exists()) {
                $firstDivision = $user->divisions()->first();
                if ($firstDivision) {
                    // Use updateQuietly to avoid triggering events
                    $user->updateQuietly(['division_id' => $firstDivision->id]);
                }
            }
        });
    }

    public function createdUsers()
    {
        return $this->hasMany(User::class, 'created_by');
    }

    // Many-to-many relationship for multiple divisions
    public function divisions()
    {
        return $this->belongsToMany(Division::class);
    }

    // Primary division relationship
    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    // Manager relationship
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    // Staff relationship (users who have this user as manager)
    public function staff(): HasMany
    {
        return $this->hasMany(User::class, 'manager_id');
    }

    public function dailyReports()
    {
        return $this->hasMany(DailyReport::class);
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

    public function getAtasanAttribute()
    {
        // First check if there's a manually assigned manager
        if ($this->manager_id) {
            return $this->manager;
        }

        // Fallback to finding any manager with appropriate role (not division-based)
        $manager = User::whereHas('roles', function($query) {
            $query->where('name', 'like', 'manager_%');
        })->first();

        // If no manager found, look for head/kepala roles
        if (!$manager) {
            $manager = User::whereHas('roles', function($query) {
                $query->where('name', 'like', 'kepala_%')
                    ->orWhere('name', 'like', 'head_%');
            })->first();
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

    // Helper method to get primary division name
    public function getPrimaryDivisionName()
    {
        return $this->division ? $this->division->name : 'No Primary Division';
    }

    // Helper method to get all division names
    public function getAllDivisionNames()
    {
        return $this->divisions->pluck('name')->toArray();
    }

    // Helper method to check if user is a manager
    public function isManager()
    {
        return $this->roles()->where('name', 'like', 'manager_%')->exists();
    }

    // Helper method to check if user is a staff
    public function isStaff()
    {
        return $this->roles()->where('name', 'like', 'staff_%')->exists();
    }

    // Helper method to get manager role name based on staff role
    public function getExpectedManagerRole()
    {
        $staffRole = $this->roles()->where('name', 'like', 'staff_%')->first();
        if ($staffRole) {
            return str_replace('staff_', 'manager_', $staffRole->name);
        }
        return null;
    }
}