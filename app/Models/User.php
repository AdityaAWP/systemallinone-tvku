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

class User extends Authenticatable implements FilamentUser
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
        'is_active',
        'created_by',
        'gender',
        'ktp',
        'address',
        'birth',
        'last_education',
        'no_phone',
        'npp',
        'division_id',
        'manager_id',
        'office_start_time',
        'office_end_time',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_admin' => 'boolean',
        'is_active' => 'boolean',
        'birth' => 'date',
        'office_start_time' => 'datetime:H:i',
        'office_end_time' => 'datetime:H:i',
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

    // Relasi untuk pembimbingan magang (Legacy - kept for backward compatibility)
    public function supervisedInterns()
    {
        return $this->belongsToMany(Intern::class, 'intern_supervisor', 'supervisor_id', 'intern_id')
            ->withPivot(['is_primary', 'notes', 'assigned_date', 'ended_date'])
            ->withTimestamps();
    }

    // Method untuk mendapatkan anak magang yang dibimbing saat ini (Legacy)
    public function activeSupervisedInterns()
    {
        return $this->supervisedInterns()
            ->whereNull('intern_supervisor.ended_date')
            ->orWhere('intern_supervisor.ended_date', '>=', now());
    }

    // Method untuk mendapatkan anak magang dengan pembimbingan utama (Legacy)
    public function primarySupervisedInterns()
    {
        return $this->supervisedInterns()
            ->wherePivot('is_primary', true);
    }

    // Method untuk cek apakah user bisa menjadi pembimbing
    public function canSuperviseInterns()
    {
        if (!$this->is_active) {
            return false;
        }

        // Cek apakah user memiliki role yang bisa menjadi pembimbing
        return $this->roles()->where(function ($query) {
            $query->where('name', 'like', 'staff_%')
                ->orWhere('name', 'like', 'manager_%')
                ->orWhere('name', 'like', 'kepala_%')
                ->orWhere('name', 'like', 'direktur_%')
                ->orWhere('name', 'hrd')
                ->orWhere('name', 'admin_magang');
        })->exists();
    }

    public function dailyReports()
    {
        return $this->hasMany(DailyReport::class);
    }

    public function uploadedFiles()
    {
        return $this->hasMany(UploadedFile::class, 'uploaded_by');
    }

    public function overtimes(): HasMany
    {
        return $this->hasMany(Overtime::class);
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
        $manager = User::whereHas('roles', function ($query) {
            $query->where('name', 'like', 'manager_%');
        })->first();

        // If no manager found, look for head/kepala roles
        if (!$manager) {
            $manager = User::whereHas('roles', function ($query) {
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

    // Override method untuk mencegah login jika user tidak aktif
    public function canAccessPanel(\Filament\Panel $panel): bool
    {
        return $this->is_active ?? true;
    }

    // Relasi pembimbingan langsung (field supervisor_id di tabel interns)
    public function directSupervisedInterns()
    {
        return $this->hasMany(Intern::class, 'supervisor_id');
    }
}
