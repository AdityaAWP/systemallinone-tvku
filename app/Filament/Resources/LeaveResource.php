<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaveResource\Pages;
use App\Filament\Widgets\LeaveStatsWidget;
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

class LeaveResource extends Resource
{
    protected static ?string $model = Leave::class;
    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationGroup = 'Menu Karyawan';
    protected static ?int $navigationSort = -1;
    protected static ?string $navigationLabel = 'Cuti';
    protected static ?string $label = 'Permohonan Cuti';

    protected function getHeaderWidgets(): array
    {
        return [
            LeaveStatsWidget::class,
        ];
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

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getNavigationBadge() > 0 ? 'primary' : null;
    }

    public static function getNavigationBadge(): ?string
    {
        $user = Auth::user();

        if ($user->hasRole('super_admin')) {
            // Super admin: tampilkan jumlah semua data cuti
            $count = Leave::count();
            return $count > 0 ? (string) $count : null;
        }

        if ($user->hasRole('hrd')) {
            // HRD: tampilkan jumlah cuti dengan status 'pending' untuk semua data
            $pendingCount = Leave::where('status', 'pending')->count();
            return $pendingCount > 0 ? (string) $pendingCount : null;
        }

        if (static::isManager($user)) {
            // Manager: tampilkan jumlah cuti 'pending' hanya untuk divisinya sendiri
            $divisionId = $user->division_id;
            $pendingCount = Leave::where('status', 'pending')
            ->whereHas('user', function ($query) use ($divisionId) {
                $query->where('division_id', $divisionId);
            })
            ->count();
            return $pendingCount > 0 ? (string) $pendingCount : null;
        }

        return null;
    }

    /**
     * Menghitung hari kerja (tidak termasuk weekend dan hari libur)
     */
    public static function calculateWorkingDays($fromDate, $toDate): int
    {
        $from = Carbon::parse($fromDate);
        $to = Carbon::parse($toDate);

        // Daftar hari libur nasional Indonesia 2025 (bisa disesuaikan atau diambil dari database)
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
                            ->options([
                                'casual' => 'Cuti Tahunan',
                                'medical' => 'Cuti Sakit',
                                'maternity' => 'Cuti Melahirkan',
                                'other' => 'Cuti Lainnya',
                            ])
                            ->required()
                            ->reactive()
                            ->disabled(!$isCreating && !$isStaff)
                            ->helperText(function (?string $state, $record) use ($user, $isStaff, $isCreating) {
                                if ($state === 'casual' && $isStaff) {
                                    // Untuk edit, gunakan user dari record, untuk create gunakan current user
                                    $targetUser = $isCreating ? $user : ($record?->user ?? $user);
                                    $quota = LeaveQuota::getUserQuota($targetUser->id);
                                    $sisaKuotaCuti = $quota ? $quota->remaining_casual_quota : 0;
                                    return "Anda memiliki {$sisaKuotaCuti} hari cuti tahunan tersisa tahun ini.";
                                }
                                return null;
                            }),

                        Forms\Components\TextInput::make('remaining_casual_leave')
                            ->label('Sisa Cuti Tahunan')
                            ->formatStateUsing(function ($record) use ($user, $isCreating) {
                                // Untuk edit, gunakan user dari record, untuk create gunakan current user
                                $targetUser = $isCreating ? $user : ($record?->user ?? $user);
                                $quota = LeaveQuota::getUserQuota($targetUser->id);
                                return $quota ? 'sisa ' . $quota->remaining_casual_quota : 'sisa 0';
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
                            ->afterStateUpdated(function (callable $set, callable $get) {
                                if ($get('from_date') && $get('to_date')) {
                                    $fromDate = $get('from_date');
                                    $toDate = $get('to_date');

                                    // Tanggal masuk kerja kembali = H+1 setelah tanggal berakhir cuti
                                    $backToWorkDate = Carbon::parse($toDate)->addDay()->format('Y-m-d');
                                    $set('back_to_work_date', $backToWorkDate);

                                    // Hitung hari kerja saja (tidak termasuk weekend dan hari libur)
                                    $workingDays = static::calculateWorkingDays($fromDate, $toDate);
                                    $set('days', $workingDays);
                                }
                            }),

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
                            ->required(),

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
                            ->label('Persetujuan Manager')
                            ->helperText('Setujui atau tolak permohonan cuti ini')
                            ->visible(fn() => $isManager && !$isCreating)
                            ->reactive(),

                        Forms\Components\Toggle::make('approval_hrd')
                            ->label('Persetujuan HRD')
                            ->helperText('Setujui atau tolak permohonan cuti ini')
                            ->visible(fn() => $user->hasRole('hrd') && !$isCreating)
                            ->reactive(),

                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Alasan Penolakan')
                            ->maxLength(500)
                            ->visible(function (callable $get) use ($user, $isCreating, $isManager) {
                                if ($isCreating) return false;

                                if ($isManager && $get('approval_manager') === false) {
                                    return true;
                                }

                                if ($user->hasRole('hrd') && $get('approval_hrd') === false) {
                                    return true;
                                }

                                return false;
                            })
                            ->required(function (callable $get) use ($user, $isManager) {
                                if ($isManager && $get('approval_manager') === false) {
                                    return true;
                                }

                                if ($user->hasRole('hrd') && $get('approval_hrd') === false) {
                                    return true;
                                }

                                return false;
                            }),
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
            ->headerActions([
                ExportAction::make()
                    ->exporter(LeaveExporter::class)
                    ->visible(fn() => $user->hasRole('hrd')),
                Tables\Actions\Action::make('Cuti Saya')
                    ->label('Cuti Saya')
                    ->icon('heroicon-o-user')
                    ->visible(fn() => $user->hasRole('hrd'))
                    ->url(fn () => url()->current() . '?tableFilters[my_leave][value]=true')
                    ->color('primary'),
                Tables\Actions\Action::make('Semua Cuti Staff')
                    ->label('Semua Cuti Staff')
                    ->icon('heroicon-o-users')
                    ->visible(fn() => $user->hasRole('hrd'))
                    ->url(fn () => url()->current() . '?tableFilters[my_leave][value]=false')
                    ->color('secondary'),
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
                       return $quota ? 'sisa ' . $quota->remaining_casual_quota : 'sisa 0';
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
                    ->visible(fn() => Auth::user()->hasRole('hrd'))
                    ->trueLabel('Cuti Saya')
                    ->falseLabel('Semua Cuti Staff')
                    ->queries(
                        true: fn(Builder $query) => $query->where('user_id', Auth::id()),
                        false: fn(Builder $query) => $query,
                        blank: fn(Builder $query) => $query,
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => Auth::user()->hasRole('hrd')),

                    Tables\Actions\ExportAction::make()
                        ->exporter(LeaveExporter::class)
                        ->label('Ekspor')
                        ->color('success')
                        ->icon('heroicon-o-document-download')
                        ->visible(fn() => Auth::user()->hasRole('hrd')),
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

        if (static::isStaff($user)) {
            // Staff hanya bisa melihat cuti miliknya sendiri
            return parent::getEloquentQuery()->where('user_id', $user->id);
        } 

        if ($user->hasRole('hrd')) {
            // HRD bisa melihat semua data cuti
            return parent::getEloquentQuery();
        }

        if (static::isManager($user) || static::isKepala($user)) {
            // Manager & Kepala hanya bisa melihat data cuti di divisinya
            $divisionId = $user->division_id;
            return parent::getEloquentQuery()->whereHas('user', function ($query) use ($divisionId) {
                $query->where('division_id', $divisionId);
            });
        }

        // Default: bisa melihat semua data cuti
        return parent::getEloquentQuery();
    }
}