<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Carbon\Carbon;

class Intern extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'fullname',
        'email',
        'password',
        'birth_date',
        'school_id',
        'institution_type',
        'division',
        'intern_division_id',
        'supervisor_id',
        'nis_nim',
        'no_phone',
        'institution_supervisor',
        'college_supervisor',
        'college_supervisor_phone',
        'start_date',
        'end_date',
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
        'birth_date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Determine if the user can access the Filament panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Only allow interns to access the intern panel
        return $panel->getId() === 'intern';
    }

    /**
     * Get the school that the intern belongs to.
     */
    public function school()
    {
        return $this->belongsTo(InternSchool::class, 'school_id');
    }

    /**
     * Get the intern division that the intern belongs to.
     */
    public function internDivision()
    {
        return $this->belongsTo(InternDivision::class, 'intern_division_id');
    }

    /**
     * Relasi untuk pembimbing magang (Legacy - kept for backward compatibility)
     */
    public function supervisors()
    {
        return $this->belongsToMany(User::class, 'intern_supervisor', 'intern_id', 'supervisor_id')
                    ->withPivot(['is_primary', 'notes', 'assigned_date', 'ended_date'])
                    ->withTimestamps();
    }

    /**
     * Method untuk mendapatkan pembimbing utama (Legacy)
     */
    public function primarySupervisor()
    {
        return $this->supervisors()->wherePivot('is_primary', true)->first();
    }

    /**
     * Method untuk mendapatkan pembimbing aktif (Legacy)
     */
    public function activeSupervisors()
    {
        return $this->supervisors()
                    ->whereNull('intern_supervisor.ended_date')
                    ->orWhere('intern_supervisor.ended_date', '>=', now());
    }

    /**
     * Relasi untuk journal magang
     */
    public function journals()
    {
        return $this->hasMany(Journal::class);
    }

    /**
     * Method untuk cek status magang
     */
    public function getInternshipStatus()
    {
        if (!$this->start_date || !$this->end_date) {
            return 'Status Tidak Diketahui';
        }

        $now = Carbon::now();
        $start = Carbon::parse($this->getRawOriginal('start_date'));
        $end = Carbon::parse($this->getRawOriginal('end_date'));
        $hampirSelesai = $end->copy()->subMonth();

        if ($now->lt($start)) {
            return 'Akan Datang';
        } elseif ($now->gt($end)) {
            return 'Selesai';
        } elseif ($now->between($hampirSelesai, $end)) {
            return 'Hampir Selesai';
        } else {
            return 'Aktif';
        }
    }

    /**
     * Get the direct supervisor of the intern (new field).
     */
    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }
}