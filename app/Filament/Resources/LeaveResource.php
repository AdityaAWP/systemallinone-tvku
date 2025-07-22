<?php

namespace App\Filament\Resources;

use App\Exports\LeaveYearlyExport;
use App\Filament\Resources\LeaveResource\Pages;
use App\Filament\Widgets\LeaveStatsWidget;
use App\Filament\Widgets\MonthlyLeaveSummaryWidget;
use App\Models\Leave;
use App\Models\LeaveQuota;
use App\Models\User;
use App\Notifications\LeaveStatusUpdated;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Actions\ExportAction;
use App\Filament\Exports\LeaveExporter;
use Dom\Text;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;

class LeaveResource extends Resource
{
    protected static ?string $model = Leave::class;
    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationGroup = 'Menu Karyawan';
    protected static ?int $navigationSort = -1;
    protected static ?string $navigationLabel = 'Cuti';
    protected static ?string $label = 'Permohonan Cuti';

    /**
     * Check if user has HRD role
     */
    private static function isHrd($user): bool
    {
        return $user->roles()->where('name', 'hrd')->exists();
    }

    /**
     * Check if user has any staff role
     */
    private static function isStaff($user): bool
    {
        return $user->roles()->where('name', 'like', 'staff%')->exists();
    }

    /**
     * Check if user has any manager role
     */
    private static function isManager($user): bool
    {
        return $user->roles()->where('name', 'like', 'manager%')->exists();
    }

    private static function isKepala($user): bool
    {
        return $user->roles()->where('name', 'like', 'kepala%')->exists();
    }

    /**
     * Check if user can edit leave record based on approval status
     */
    private static function canEditLeave($user, $record): bool
    {
        // Staff tidak bisa edit jika sudah ada approval
        if (static::isStaff($user)) {
            return !($record->approval_manager || $record->approval_hrd);
        }
        
        // HRD bisa edit jika HRD belum approve
        if (static::isHrd($user)) {
            return $record->approval_hrd !== true;
        }
        
        // Manager/Kepala bisa edit jika Manager/Kepala belum approve
        if (static::isManager($user) || static::isKepala($user)) {
            return $record->approval_manager !== true;
        }
        
        // Default: tidak bisa edit
        return false;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getNavigationBadge() > 0 ? 'primary' : null;
    }

    public static function getNavigationBadge(): ?string
    {
        $user = Auth::user();
        // Jika super_admin, hanya tampilkan jumlah cuti milik sendiri
        if ($user->roles()->where('name', 'super_admin')->exists()) {
            $count = Leave::where('user_id', $user->id)->count();
            return $count > 0 ? (string) $count : null;
        }

        if (static::isHrd($user)) {
            $count = Leave::count();
            return $count > 0 ? (string) $count : null;
        }

        if (static::isManager($user) || static::isKepala($user)) {
            $userDivisionIds = $user->divisions()->pluck('divisions.id')->toArray();

            if (empty($userDivisionIds) && $user->division_id) {
            $userDivisionIds = [$user->division_id];
            }

            $count = Leave::whereHas('user', function ($query) use ($userDivisionIds) {
                $query->whereIn('division_id', $userDivisionIds);
            })
            ->count();
            return $count > 0 ? (string) $count : null;
        }

        if (static::isStaff($user)) {
            $count = Leave::where('user_id', $user->id)->count();
            return $count > 0 ? (string) $count : null;
        }

        return null;
    }

    public static function calculateWorkingDays($fromDate, $toDate): int
    {
        $from = Carbon::parse($fromDate);
        $to = Carbon::parse($toDate);

         $holidays = [
            '2025-01-01', // Tahun Baru
            '2025-01-29', // Tahun Baru Imlek
            '2025-02-12', // Isra Mi'raj
            '2025-03-14', // Hari Suci Nyepi
            '2025-03-29', // Wafat Isa Al-Masih
            '2025-03-30', // Idul Fitri
            '2025-03-31', // Idul Fitri
            '2025-04-01', // Cuti Bersama Idul Fitri
            '2025-05-01', // Hari Buruh
            '2025-05-12', // Hari Raya Waisak
            '2025-05-29', // Kenaikan Isa Al-Masih
            '2025-06-01', // Hari Lahir Pancasila
            '2025-06-06', // Idul Adha
            '2025-06-27', // Tahun Baru Islam
            '2025-08-17', // Hari Kemerdekaan RI
            '2025-09-05', // Maulid Nabi Muhammad SAW
            '2025-12-25', // Hari Raya Natal
        ];

        $workingDays = 0;
        $current = $from->copy();

        while ($current->lte($to)) {
            // Skip weekend (Sabtu = 6, Minggu = 0)
            if (!$current->isWeekend()) {
                // Skip hari libur nasional
                if (!in_array($current->format('Y-m-d'), $holidays)) {
                    $workingDays++;
                }
            }
            $current->addDay();
        }

        return $workingDays;
    }

    public static function form(Form $form): Form
    {
        $user = Auth::user();
        $isStaff = static::isStaff($user);
        $isManager = static::isManager($user);
        $isKepala = static::isKepala($user);
        $isHrd = static::isHrd($user);
        $isCreating = $form->getOperation() === 'create';

        return $form
            ->schema([
                Forms\Components\Section::make('Detail Permohonan Cuti')
                    ->schema([
                        Forms\Components\TextInput::make('employee_name')
                            ->label('Nama Karyawan')
                            ->formatStateUsing(function ($record) use ($user, $isCreating) {
                                if ($isCreating) {
                                    return $user->name;
                                }
                                return $record?->user?->name ?? $user->name;
                            })
                            ->disabled()
                            ->required(),

                        Forms\Components\TextInput::make('employee_npp')
                            ->label('NPP')
                            ->formatStateUsing(function ($record) use ($user, $isCreating) {
                                if ($isCreating) {
                                    return $user->npp;
                                }
                                return $record?->user?->npp ?? $user->npp;
                            })
                            ->disabled()
                            ->required(),

                        Forms\Components\TextInput::make('employee_division')
                            ->label('Divisi')
                            ->formatStateUsing(function ($record) use ($user, $isCreating) {
                                if ($isCreating) {
                                    return $user->division?->name ?? '-';
                                }
                                return $record?->user?->division?->name ?? $user->division?->name ?? '-';
                            })
                            ->disabled()
                            ->required(),

                        Forms\Components\TextInput::make('employee_position')
                            ->label('Jabatan')
                            ->formatStateUsing(function ($record) use ($user, $isCreating) {
                                if ($isCreating) {
                                    return $user->position ?? '-';
                                }
                                return $record?->user?->position ?? $user->position ?? '-';
                            })
                            ->disabled()
                            ->required(),

                        Forms\Components\Select::make('leave_type')
                            ->label('Jenis Cuti')
                            ->options(function () use ($user, $isStaff, $isCreating) {
                                $options = [
                                    'medical' => 'Cuti Sakit',
                                    'maternity' => 'Cuti Melahirkan',
                                    'other' => 'Cuti Lainnya',
                                ];
                                
                                // Hanya tampilkan opsi "Cuti Tahunan" jika masih ada kesempatan dan hari
                                if ($isStaff && $isCreating) {
                                    $quota = LeaveQuota::getUserQuota($user->id);
                                    $sisaKesempatan = $quota ? $quota->remaining_casual_quota : 0;
                                    
                                    // Hitung sisa hari cuti tahunan
                                    $currentYear = date('Y');
                                    $approvedCasualLeaves = \App\Models\Leave::where('user_id', $user->id)
                                        ->where('leave_type', 'casual')
                                        ->whereYear('from_date', $currentYear)
                                        ->where('status', 'approved')
                                        ->get();
                                    
                                    $usedDays = 0;
                                    foreach ($approvedCasualLeaves as $leave) {
                                        $usedDays += static::calculateWorkingDays($leave->from_date, $leave->to_date);
                                    }
                                    $remainingDays = 12 - $usedDays;
                                    
                                    // Tampilkan opsi cuti tahunan hanya jika masih ada kesempatan DAN hari
                                    if ($sisaKesempatan > 0 && $remainingDays > 0) {
                                        $options = ['casual' => 'Cuti Tahunan'] + $options;
                                    }
                                } else {
                                    // Untuk edit atau non-staff, tetap tampilkan semua opsi
                                    $options = ['casual' => 'Cuti Tahunan'] + $options;
                                }
                                
                                return $options;
                            })
                            ->required()
                            ->reactive()
                            ->disabled(!$isCreating && !$isStaff)
                            ->rules([
                                function ($get) use ($user, $isStaff, $isCreating) {
                                    return function (string $attribute, $value, \Closure $fail) use ($get, $user, $isStaff, $isCreating) {
                                        if ($value === 'casual' && $isStaff && $isCreating) {
                                            $quota = LeaveQuota::getUserQuota($user->id);
                                            $sisaKesempatan = $quota ? $quota->remaining_casual_quota : 0;
                                            
                                            // Cek kesempatan
                                            if ($sisaKesempatan <= 0) {
                                                $fail('Kesempatan cuti tahunan Anda sudah habis untuk tahun ini.');
                                                return;
                                            }
                                            
                                            // Hitung sisa hari cuti tahunan
                                            $currentYear = date('Y');
                                            $approvedCasualLeaves = \App\Models\Leave::where('user_id', $user->id)
                                                ->where('leave_type', 'casual')
                                                ->whereYear('from_date', $currentYear)
                                                ->where('status', 'approved')
                                                ->get();
                                            
                                            $usedDays = 0;
                                            foreach ($approvedCasualLeaves as $leave) {
                                                $usedDays += static::calculateWorkingDays($leave->from_date, $leave->to_date);
                                            }
                                            $remainingDays = 12 - $usedDays;
                                            
                                            // Cek sisa hari
                                            if ($remainingDays <= 0) {
                                                $fail('Kuota 12 hari cuti tahunan Anda sudah habis untuk tahun ini.');
                                            }
                                        }
                                    };
                                }
                            ])
                            ->helperText(function (?string $state, $record) use ($user, $isStaff, $isCreating) {
                                if ($state === 'casual' && $isStaff) {
                                    // Untuk edit, gunakan user dari record, untuk create gunakan current user
                                    $targetUser = $isCreating ? $user : ($record?->user ?? $user);
                                    $quota = LeaveQuota::getUserQuota($targetUser->id);
                                    $sisaKesempatan = $quota ? $quota->remaining_casual_quota : 0;
                                    
                                    // Hitung total hari cuti tahunan yang sudah digunakan tahun ini
                                    $currentYear = date('Y');
                                    $approvedCasualLeaves = \App\Models\Leave::where('user_id', $targetUser->id)
                                        ->where('leave_type', 'casual')
                                        ->whereYear('from_date', $currentYear)
                                        ->where('status', 'approved')
                                        ->get();
                                    
                                    $usedDays = 0;
                                    foreach ($approvedCasualLeaves as $leave) {
                                        $usedDays += static::calculateWorkingDays($leave->from_date, $leave->to_date);
                                    }
                                    $remainingDays = 12 - $usedDays;
                                    
                                    if ($sisaKesempatan <= 0) {
                                        return "⚠️ Kesempatan cuti tahunan Anda sudah habis untuk tahun ini.";
                                    }
                                    if ($remainingDays <= 0) {
                                        return "⚠️ Kuota 12 hari cuti tahunan Anda sudah habis untuk tahun ini.";
                                    }
                                    return "Sisa {$sisaKesempatan} kesempatan dan {$remainingDays} hari cuti tahunan tahun ini.";
                                } elseif ($isStaff && $isCreating) {
                                    // Tampilkan info kuota saat belum memilih jenis cuti
                                    $quota = LeaveQuota::getUserQuota($user->id);
                                    $sisaKesempatan = $quota ? $quota->remaining_casual_quota : 0;
                                    
                                    // Hitung sisa hari
                                    $currentYear = date('Y');
                                    $approvedCasualLeaves = \App\Models\Leave::where('user_id', $user->id)
                                        ->where('leave_type', 'casual')
                                        ->whereYear('from_date', $currentYear)
                                        ->where('status', 'approved')
                                        ->get();
                                    
                                    $usedDays = 0;
                                    foreach ($approvedCasualLeaves as $leave) {
                                        $usedDays += static::calculateWorkingDays($leave->from_date, $leave->to_date);
                                    }
                                    $remainingDays = 12 - $usedDays;
                                    
                                    if ($sisaKesempatan <= 0 || $remainingDays <= 0) {
                                        return "⚠️ Kuota cuti tahunan Anda sudah habis. Anda hanya bisa mengajukan cuti sakit, melahirkan, atau lainnya.";
                                    }
                                }
                                return null;
                            }),

                        Forms\Components\TextInput::make('remaining_casual_leave')
                            ->label('Sisa Cuti Tahunan')
                            ->formatStateUsing(function ($record) use ($user, $isCreating) {
                                // Untuk edit, gunakan user dari record, untuk create gunakan current user
                                $targetUser = $isCreating ? $user : ($record?->user ?? $user);
                                $quota = LeaveQuota::getUserQuota($targetUser->id);
                                $sisaKesempatan = $quota ? $quota->remaining_casual_quota : 0;
                                
                                // Hitung sisa hari cuti tahunan
                                $currentYear = date('Y');
                                $approvedCasualLeaves = \App\Models\Leave::where('user_id', $targetUser->id)
                                    ->where('leave_type', 'casual')
                                    ->whereYear('from_date', $currentYear)
                                    ->where('status', 'approved')
                                    ->get();
                                
                                $usedDays = 0;
                                foreach ($approvedCasualLeaves as $leave) {
                                    $usedDays += static::calculateWorkingDays($leave->from_date, $leave->to_date);
                                }
                                $remainingDays = 12 - $usedDays;
                                
                                return "{$sisaKesempatan} Kesempatan";
                            })
                            ->disabled(),

                        Forms\Components\DatePicker::make('from_date')
                            ->label('Tanggal Mulai Cuti')
                            ->helperText('Tanggal pertama tidak masuk kerja')
                            ->required()
                            ->disabled(!$isCreating && !$isStaff)
                            ->minDate(fn() => $isCreating ? Carbon::now() : null)
                            ->reactive(),

                        Forms\Components\DatePicker::make('to_date')
                            ->label('Tanggal Berakhir Cuti')
                            ->helperText('Tanggal terakhir tidak masuk kerja')
                            ->required()
                            ->disabled(!$isCreating && !$isStaff)
                            ->minDate(fn(callable $get) => $get('from_date') ? Carbon::parse($get('from_date')) : ($isCreating ? Carbon::now() : null))
                            ->reactive()
                            ->afterStateUpdated(function (callable $set, callable $get) use ($user, $isCreating) {
                                if ($get('from_date') && $get('to_date')) {
                                    $fromDate = $get('from_date');
                                    $toDate = $get('to_date');

                                    // Tanggal masuk kerja kembali = H+1 setelah tanggal berakhir cuti
                                    $backToWorkDate = Carbon::parse($toDate)->addDay()->format('Y-m-d');
                                    $set('back_to_work_date', $backToWorkDate);

                                    // Hitung hari kerja saja (tidak termasuk weekend dan hari libur)
                                    $workingDays = static::calculateWorkingDays($fromDate, $toDate);
                                    $set('days', $workingDays);
                                    
                                    // Validasi kuota cuti tahunan jika jenisnya casual
                                    if ($get('leave_type') === 'casual' && $isCreating) {
                                        $quota = LeaveQuota::getUserQuota($user->id);
                                        $sisaKesempatan = $quota ? $quota->remaining_casual_quota : 0;
                                        
                                        // Hitung sisa hari cuti tahunan
                                        $currentYear = date('Y');
                                        $approvedCasualLeaves = \App\Models\Leave::where('user_id', $user->id)
                                            ->where('leave_type', 'casual')
                                            ->whereYear('from_date', $currentYear)
                                            ->where('status', 'approved')
                                            ->get();
                                        
                                        $usedDays = 0;
                                        foreach ($approvedCasualLeaves as $leave) {
                                            $usedDays += static::calculateWorkingDays($leave->from_date, $leave->to_date);
                                        }
                                        $remainingDays = 12 - $usedDays;
                                        
                                        if ($sisaKesempatan <= 0) {
                                            $set('days_error', "Kesempatan cuti tahunan Anda sudah habis untuk tahun ini.");
                                        } elseif ($workingDays > $remainingDays) {
                                            $set('days_error', "Hari cuti yang diminta ({$workingDays} hari) melebihi sisa kuota cuti tahunan Anda ({$remainingDays} hari).");
                                        } else {
                                            $set('days_error', null);
                                        }
                                    } else {
                                        $set('days_error', null);
                                    }
                                }
                            })
                            ->rules([
                                function ($get) use ($user, $isCreating) {
                                    return function (string $attribute, $value, \Closure $fail) use ($get, $user, $isCreating) {
                                        if ($get('leave_type') === 'casual' && $isCreating && $get('from_date') && $value) {
                                            $quota = LeaveQuota::getUserQuota($user->id);
                                            $sisaKesempatan = $quota ? $quota->remaining_casual_quota : 0;
                                            
                                            // Cek kesempatan
                                            if ($sisaKesempatan <= 0) {
                                                $fail('Kesempatan cuti tahunan Anda sudah habis untuk tahun ini.');
                                                return;
                                            }
                                            
                                            // Hitung hari kerja berdasarkan tanggal
                                            $workingDays = static::calculateWorkingDays($get('from_date'), $value);
                                            
                                            // Hitung sisa hari cuti tahunan
                                            $currentYear = date('Y');
                                            $approvedCasualLeaves = \App\Models\Leave::where('user_id', $user->id)
                                                ->where('leave_type', 'casual')
                                                ->whereYear('from_date', $currentYear)
                                                ->where('status', 'approved')
                                                ->get();
                                            
                                            $usedDays = 0;
                                            foreach ($approvedCasualLeaves as $leave) {
                                                $usedDays += static::calculateWorkingDays($leave->from_date, $leave->to_date);
                                            }
                                            $remainingDays = 12 - $usedDays;
                                            
                                            // Cek apakah hari yang diminta melebihi sisa kuota
                                            if ($workingDays > $remainingDays) {
                                                $fail("Hari cuti yang diminta ({$workingDays} hari) melebihi sisa kuota cuti tahunan Anda ({$remainingDays} hari).");
                                            }
                                        }
                                    };
                                }
                            ]),

                        Forms\Components\DatePicker::make('back_to_work_date')
                            ->label('Tanggal Masuk Kerja Kembali')
                            ->helperText('Tanggal pertama masuk kerja setelah cuti (otomatis terisi)')
                            ->disabled()
                            ->formatStateUsing(function ($record) {
                                // Jika editing dan ada to_date, hitung back_to_work_date
                                if ($record && $record->to_date) {
                                    return Carbon::parse($record->to_date)->addDay()->format('Y-m-d');
                                }
                                return null;
                            })
                            ->required(),

                        Forms\Components\TextInput::make('days')
                            ->label('Jumlah Hari Kerja')
                            ->helperText('Hanya hari kerja yang dihitung (tidak termasuk weekend dan hari libur)')
                            ->numeric()
                            ->disabled()
                            ->formatStateUsing(function ($record) {
                                // Jika editing dan ada from_date & to_date, hitung ulang working days
                                if ($record && $record->from_date && $record->to_date) {
                                    return static::calculateWorkingDays($record->from_date, $record->to_date);
                                }
                                return $record?->days ?? 0;
                            })
                            ->reactive()
                            ->afterStateUpdated(function (callable $set, callable $get) use ($user, $isCreating) {
                                // Validasi kuota cuti tahunan jika jenisnya casual
                                if ($get('leave_type') === 'casual' && $isCreating) {
                                    $days = $get('days');
                                    $quota = LeaveQuota::getUserQuota($user->id);
                                    $sisaKesempatan = $quota ? $quota->remaining_casual_quota : 0;
                                    
                                    // Hitung sisa hari cuti tahunan
                                    $currentYear = date('Y');
                                    $approvedCasualLeaves = \App\Models\Leave::where('user_id', $user->id)
                                        ->where('leave_type', 'casual')
                                        ->whereYear('from_date', $currentYear)
                                        ->where('status', 'approved')
                                        ->get();
                                    
                                    $usedDays = 0;
                                    foreach ($approvedCasualLeaves as $leave) {
                                        $usedDays += static::calculateWorkingDays($leave->from_date, $leave->to_date);
                                    }
                                    $remainingDays = 12 - $usedDays;
                                    
                                    if ($sisaKesempatan <= 0) {
                                        $set('days_error', "Kesempatan cuti tahunan Anda sudah habis untuk tahun ini.");
                                    } elseif ($days > $remainingDays) {
                                        $set('days_error', "Hari cuti yang diminta ({$days} hari) melebihi sisa kuota cuti tahunan Anda ({$remainingDays} hari).");
                                    } else {
                                        $set('days_error', null);
                                    }
                                } else {
                                    $set('days_error', null);
                                }
                            })
                            ->rules([
                                function ($get) use ($user, $isCreating) {
                                    return function (string $attribute, $value, \Closure $fail) use ($get, $user, $isCreating) {
                                        if ($get('leave_type') === 'casual' && $isCreating) {
                                            $quota = LeaveQuota::getUserQuota($user->id);
                                            $sisaKesempatan = $quota ? $quota->remaining_casual_quota : 0;
                                            
                                            // Cek kesempatan
                                            if ($sisaKesempatan <= 0) {
                                                $fail('Kesempatan cuti tahunan Anda sudah habis untuk tahun ini.');
                                                return;
                                            }
                                            
                                            // Hitung sisa hari cuti tahunan
                                            $currentYear = date('Y');
                                            $approvedCasualLeaves = \App\Models\Leave::where('user_id', $user->id)
                                                ->where('leave_type', 'casual')
                                                ->whereYear('from_date', $currentYear)
                                                ->where('status', 'approved')
                                                ->get();
                                            
                                            $usedDays = 0;
                                            foreach ($approvedCasualLeaves as $leave) {
                                                $usedDays += static::calculateWorkingDays($leave->from_date, $leave->to_date);
                                            }
                                            $remainingDays = 12 - $usedDays;
                                            
                                            // Cek apakah hari yang diminta melebihi sisa kuota
                                            if ($value > $remainingDays) {
                                                $fail("Hari cuti yang diminta ({$value} hari) melebihi sisa kuota cuti tahunan Anda ({$remainingDays} hari).");
                                            }
                                        }
                                    };
                                }
                            ])
                            ->required(),

                        Forms\Components\Placeholder::make('days_error')
                            ->label('')
                            ->content(fn(callable $get) => $get('days_error'))
                            ->visible(fn(callable $get) => !empty($get('days_error')))
                            ->extraAttributes(['class' => 'text-red-600 font-medium']),

                        Forms\Components\Textarea::make('reason')
                            ->label('Keterangan')
                            ->required()
                            ->maxLength(500)
                            ->disabled(!$isCreating && !$isStaff),

                        Forms\Components\FileUpload::make('attachment')
                            ->label('Lampiran (jika ada)')
                            ->directory('lampiran-cuti')
                            ->disabled(!$isCreating && !$isStaff)
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png']),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Bagian Persetujuan')
                    ->schema([
                        Forms\Components\Toggle::make('approval_manager')
                            ->label('Persetujuan Manager/Kepala')
                            ->helperText('Setujui atau tolak permohonan cuti ini')
                            ->visible(fn() => ($isManager || $isKepala) && !$isCreating)
                            ->hiddenOn('view')
                            ->reactive(),

                        Forms\Components\Toggle::make('approval_hrd')
                            ->label('Persetujuan HRD')
                            ->helperText('Setujui atau tolak permohonan cuti ini')
                            ->visible(fn() => static::isHrd($user) && !$isCreating)
                            ->hiddenOn('view')
                            ->reactive(),

                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Alasan Penolakan')
                            ->maxLength(500)
                            ->visible(function (callable $get) use ($user, $isCreating, $isManager, $isKepala) {
                                if ($isCreating) return false;

                                if (($isManager || $isKepala) && $get('approval_manager') === false) {
                                    return true;
                                }

                                if (static::isHrd($user) && $get('approval_hrd') === false) {
                                    return true;
                                }

                                return false;
                            })
                            ->required(function (callable $get) use ($user, $isManager, $isKepala) {
                                if (($isManager || $isKepala) && $get('approval_manager') === false) {
                                    return true;
                                }

                                if (static::isHrd($user) && $get('approval_hrd') === false) {
                                    return true;
                                }

                                return false;
                            })
                            ->hiddenOn('view'),
                    ])
                    ->visible(!$isCreating),

                Forms\Components\Section::make('Informasi Status')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status Saat Ini')
                            ->options([
                                'pending' => 'Menunggu',
                                'approved' => 'Disetujui',
                                'rejected' => 'Ditolak',
                            ])
                            ->disabled()
                            ->default('pending')
                            ->visible(!$isCreating)
                    ])
                    ->visible($isStaff && !$isCreating),
            ]);
    }

    public static function table(Table $table): Table
    {
        $user = Auth::user();
        $isStaff = static::isStaff($user);

        return $table
        ->recordUrl(null)
            ->headerActions([
                Tables\Actions\Action::make('reset_to_all')
                    ->label('Semua Cuti Staff')
                    ->icon('heroicon-o-users')
                    ->visible(fn() => static::isHrd($user) || static::isManager($user) || static::isKepala($user))
                    ->url(fn() => request()->url())
                    ->color(fn() => !request()->hasAny(['tableFilters']) || request()->input('tableFilters.my_leave.value') === 'false' ? 'primary' : 'gray'),

                Tables\Actions\Action::make('filter_my_leave')
                    ->label('Cuti Saya')
                    ->icon('heroicon-o-user')
                    ->visible(fn() => static::isHrd($user) || static::isManager($user) || static::isKepala($user))
                    ->url(fn() => request()->url() . '?tableFilters[my_leave][value]=true')
                    ->color(fn() => request()->input('tableFilters.my_leave.value') === 'true' ? 'primary' : 'gray'),

                Tables\Actions\Action::make('export_leave')
                    ->label('Ekspor Excel')
                    ->color('success')
                    ->icon('heroicon-o-document-arrow-down')
                    ->form([
                        Select::make('year')
                            ->label('Tahun')
                            ->options(function () {
                                $years = [];
                                $currentYear = now()->year;

                                // Generate 5 tahun mundur dari tahun sekarang
                                for ($i = 0; $i < 5; $i++) {
                                    $year = $currentYear - $i;
                                    $years[$year] = $year;
                                }

                                return $years;
                            })
                            ->required()
                            ->default(now()->year),
                        Select::make('export_type')
                            ->label('Jenis Export')
                            ->options(function () {
                                $user = Auth::user();
                                $options = ['personal' => 'Data Pribadi Saya'];

                                // Cek role HRD dengan method checking yang sudah ada
                                if (static::isHrd($user)) {
                                    $options['all'] = 'Semua Data Staff';
                                } elseif (static::isManager($user) || static::isKepala($user)) {
                                    $options['division'] = 'Semua Data Staff Divisi Saya';
                                }

                                return $options;
                            })
                            ->default('personal')
                            ->visible(fn() => static::isHrd(Auth::user()) || static::isManager(Auth::user()) || static::isKepala(Auth::user()))
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $year = $data['year'];
                        $user = Auth::user();

                        // Tentukan userId dan divisionIds berdasarkan pilihan export
                        if (static::isHrd($user) && isset($data['export_type']) && $data['export_type'] === 'all') {
                            $userId = null; // Export semua data
                            $divisionIds = null;
                            $filename = "laporan_cuti_semua_staff_{$year}.xlsx";
                        } elseif ((static::isManager($user) || static::isKepala($user)) && isset($data['export_type']) && $data['export_type'] === 'division') {
                            $userId = null; // Export semua data staff dari divisi yang dikelola
                            $userDivisionIds = $user->divisions()->pluck('divisions.id')->toArray();

                            // Jika tidak ada divisi dari many-to-many, fallback ke primary division
                            if (empty($userDivisionIds) && $user->division_id) {
                                $userDivisionIds = [$user->division_id];
                            }

                            $divisionIds = $userDivisionIds;
                            $divisionNames = $user->divisions()->pluck('name')->implode('_');
                            if (empty($divisionNames) && $user->division) {
                                $divisionNames = $user->division->name;
                            }
                            $filename = "laporan_cuti_divisi_{$divisionNames}_{$year}.xlsx";
                        } else {
                            $userId = $user->id; // Export data pribadi
                            $divisionIds = null;
                            $filename = "laporan_cuti_{$year}.xlsx";
                        }

                        return (new LeaveYearlyExport($year, $userId, $divisionIds))->download($filename);
                    }),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Nama Karyawan')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.npp')
                    ->label('NPP')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.division.name')
                    ->label('Divisi')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.position')
                    ->label('Jabatan')
                    ->formatStateUsing(fn($state) => $state ?: '-')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('leave_type')
                    ->label('Jenis Cuti')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'casual' => 'success',
                        'medical' => 'warning',
                        'maternity' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'casual' => 'Cuti Tahunan',
                        'medical' => 'Cuti Sakit',
                        'maternity' => 'Cuti Melahirkan',
                        'other' => 'Cuti Lainnya',
                        default => 'Tidak Diketahui',
                    }),

                Tables\Columns\TextColumn::make('from_date')
                    ->label('Mulai Cuti')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('to_date')
                    ->label('Berakhir Cuti')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('back_to_work_date')
                    ->label('Masuk Kembali')
                    ->getStateUsing(function ($record) {
                        // Tanggal masuk kerja kembali = H+1 setelah tanggal berakhir cuti
                        if ($record->to_date) {
                            return \Carbon\Carbon::parse($record->to_date)->addDay()->format('Y-m-d');
                        }
                        return null;
                    })
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('days')
                    ->label('Jml. Hari Cuti')
                    ->alignCenter()
                    ->suffix(' hari')
                    ->tooltip('Hanya hari kerja yang dihitung (tidak termasuk weekend dan hari libur)')
                    ->getStateUsing(function ($record) {
                        if ($record->from_date && $record->to_date) {
                            // Panggil fungsi calculateWorkingDays dari LeaveResource
                            return \App\Filament\Resources\LeaveResource::calculateWorkingDays($record->from_date, $record->to_date);
                        }
                        return 0;
                    }),

                Tables\Columns\TextColumn::make('remaining_casual_leave')
                    ->label('Sisa Cuti Tahunan')
                    ->alignCenter()
                    ->getStateUsing(function ($record) {
                        $quota = $record->user?->leaveQuotas?->first();
                        $sisaKesempatan = $quota ? $quota->remaining_casual_quota : 0;
                        
                        // Hitung sisa hari cuti tahunan
                        $currentYear = date('Y');
                        $approvedCasualLeaves = \App\Models\Leave::where('user_id', $record->user_id)
                            ->where('leave_type', 'casual')
                            ->whereYear('from_date', $currentYear)
                            ->where('status', 'approved')
                            ->get();
                        
                        $usedDays = 0;
                        foreach ($approvedCasualLeaves as $leave) {
                            $usedDays += \App\Filament\Resources\LeaveResource::calculateWorkingDays($leave->from_date, $leave->to_date);
                        }
                        $remainingDays = 12 - $usedDays;
                        
                        return "{$sisaKesempatan} Kesempatan";
                    })
                    ->sortable(),

                Tables\Columns\IconColumn::make('approval_manager')
                    ->label('Manager')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('approval_hrd')
                    ->label('HRD')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->alignCenter(),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'danger' => 'rejected',
                        'warning' => 'pending',
                        'success' => 'approved',
                    ]),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Diajukan Pada')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('leave_type')
                    ->label('Jenis Cuti')
                    ->options([
                        'casual' => 'Cuti Tahunan',
                        'medical' => 'Cuti Sakit',
                        'maternity' => 'Cuti Melahirkan',
                        'other' => 'Cuti Lainnya',
                    ]),

                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('to'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('from_date', '>=', $date),
                            )
                            ->when(
                                $data['to'],
                                fn(Builder $query, $date): Builder => $query->whereDate('to_date', '<=', $date),
                            );
                    }),

                Tables\Filters\TernaryFilter::make('my_leave')
                    ->label('Tampilkan Cuti Saya')
                    ->visible(fn() => static::isHrd(Auth::user()) || static::isManager(Auth::user()) || static::isKepala(Auth::user()))
                    ->trueLabel('Cuti Saya')
                    ->falseLabel('Semua Cuti Staff')
                    ->queries(
                        true: fn(Builder $query) => $query->where('user_id', Auth::id()),
                        false: fn(Builder $query) => $query,
                        blank: fn(Builder $query) => $query,
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Lihat'),
                Tables\Actions\EditAction::make()
                    ->label('Edit')
                    ->disabled(function (Leave $record) {
                        $user = Auth::user();
                        return !static::canEditLeave($user, $record);
                    }),
                Tables\Actions\Action::make('export_individual')
                    ->label('Export Data')
                    ->icon('heroicon-o-document-text')
                    ->color('success')
                    ->tooltip('Export laporan cuti karyawan ini')
                    ->visible(fn() => static::isHrd(Auth::user()))
                    ->modalHeading(fn(Leave $record) => 'Export Laporan Cuti - ' . $record->user->name)
                    ->modalDescription('Export laporan cuti karyawan ini')
                    ->modalWidth('md')
                    ->form([
                        TextInput::make('user_info')
                            ->label('Karyawan')
                            ->disabled()
                            ->default(fn(Leave $record) => $record->user->name . ' (' . ($record->user->npp ?? 'No NPP') . ')'),
                        Select::make('year')
                            ->label('Tahun')
                            ->options(function () {
                                $years = [];
                                $currentYear = now()->year;

                                // Generate 5 tahun mundur dari tahun sekarang
                                for ($i = 0; $i < 5; $i++) {
                                    $year = $currentYear - $i;
                                    $years[$year] = $year;
                                }

                                return $years;
                            })
                            ->required()
                            ->default(now()->year),
                    ])
                    ->action(function (Leave $record, array $data) {
                        $user = $record->user;
                        $year = $data['year'];
                        $userName = str_replace(' ', '_', $user->name);

                        $filename = "laporan_cuti_{$userName}_{$year}.xlsx";
                        return (new LeaveYearlyExport($year, $user->id))->download($filename);
                    }),

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => static::isHrd(Auth::user())),

                    Tables\Actions\ExportAction::make()
                        ->exporter(LeaveExporter::class)
                        ->label('Ekspor')
                        ->color('success')
                        ->icon('heroicon-o-document-download')
                        ->visible(fn() => static::isHrd(Auth::user())),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeave::route('/'),
            'create' => Pages\CreateLeave::route('/create'),
            'edit' => Pages\EditLeave::route('/{record}/edit'),
        ];
    }


    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        
        // MODIFIKASI: Mulai query dengan filter user aktif sebagai dasar.
        // Ini memastikan SEMUA data cuti yang diambil HANYA dari user yang aktif.
        $query = parent::getEloquentQuery()->whereHas('user', function ($query) {
            $query->where('is_active', true);
        });

        // Logika untuk staff biasa tetap, beroperasi pada query yang sudah difilter.
        if (static::isStaff($user) && !static::isHrd($user) && !static::isManager($user) && !static::isKepala($user)) {
            return $query->where('user_id', $user->id);
        }

        // Logika untuk HRD, Manager, dan Kepala.
        if (static::isHrd($user) || static::isManager($user) || static::isKepala($user)) {

            // Langkah 1: Dapatkan ID karyawan yang dapat diakses DAN AKTIF.
            $accessibleUsersQuery = \App\Models\User::query()
                ->where('is_active', true); // <-- TAMBAHKAN FILTER INI

            // Logika pembatasan berdasarkan divisi tetap sama.
            if (!static::isHrd($user)) {
                // ... (logika divisi yang sudah ada tidak perlu diubah)
                $userDivisionIds = $user->divisions()->pluck('divisions.id')->toArray();
                if (empty($userDivisionIds) && $user->division_id) {
                    $userDivisionIds = [$user->division_id];
                }

                if (!empty($userDivisionIds)) {
                    $accessibleUsersQuery->where(function ($q) use ($userDivisionIds, $user) {
                        $q->whereIn('division_id', $userDivisionIds)
                          ->orWhere('id', $user->id);
                    });
                } else {
                    $accessibleUsersQuery->where('id', $user->id);
                }
            }
            
            // Langkah 2: Tampilkan SEMUA cuti dari user yang dapat diakses, bukan hanya yang terakhir
            $accessibleUserIds = $accessibleUsersQuery->pluck('id')->toArray();
            
            // Langkah 3: Filter query utama untuk menampilkan semua cuti dari user yang dapat diakses
            return $query->whereIn('user_id', $accessibleUserIds);
        }

        // Fallback default.
        return $query->where('user_id', $user->id);
    }

}
