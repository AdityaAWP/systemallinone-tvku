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

        if ($user->hasAnyRole(['hrd', 'manager',])) {
            // HRD & Manager: tampilkan jumlah cuti dengan status 'pending'
            $pendingCount = Leave::where('status', 'pending')->count();
            return $pendingCount > 0 ? (string) $pendingCount : null;
        }

        return null;
    }

    /**
     * Menghitung hari kerja (tidak termasuk weekend dan hari libur)
     */
    private static function calculateWorkingDays($fromDate, $toDate): int
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
        $isStaff = $user->hasAnyRole(['staff', 'staff_keuangan']);
        $isCreating = $form->getOperation() === 'create';

        $sisaKuotaCuti = 0;
        if ($isStaff) {
            $quota = LeaveQuota::getUserQuota($user->id);
            $sisaKuotaCuti = $quota->remaining_casual_quota;
        }

        return $form
            ->schema([
                Forms\Components\Section::make('Detail Permohonan Cuti')
                    ->schema([
                        Forms\Components\TextInput::make('user.name')
                            ->label('Karyawan')
                            ->default($user->name)
                            ->required()
                            ->visible(!$isStaff)
                            ->default(fn() => $isStaff ? $user->id : $user->name),
                        
                        TextInput::make('user.npp')
                            ->label('NPP')
                            ->default($user->npp)
                            ->required()
                            ->visible(!$isStaff)
                            ->default(fn() => $isStaff ? $user->npp : $user->npp),
                        
                        TextInput::make('user.division.name')
                            ->label('Divisi')
                            ->default($user->division?->name)
                            ->required()
                            ->visible(!$isStaff)
                            ->default(fn() => $isStaff ? $user->division?->name : $user->division?->name),
                        
                        TextInput::make('user.position')
                            ->label('Jabatan')
                            ->default($user->position)
                            ->required()
                            ->visible(!$isStaff)
                            ->default(fn() => $isStaff ? $user->position : $user->position),

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
                            ->helperText(fn(?string $state) => $state === 'casual' && $isStaff ? "Anda memiliki {$sisaKuotaCuti} hari cuti tahunan tersisa tahun ini." : null),

                        Forms\Components\DatePicker::make('from_date')
                            ->label('Tanggal Mulai Cuti')
                            ->helperText('Tanggal pertama tidak masuk kerja')
                            ->required()
                            ->disabled(!$isCreating && !$isStaff)
                            ->minDate(fn() => Carbon::now())
                            ->reactive(),

                        Forms\Components\DatePicker::make('back_to_work_date')
                            ->label('Tanggal Masuk Kerja Kembali')
                            ->helperText('Tanggal pertama masuk kerja setelah cuti')
                            ->required()
                            ->disabled(!$isCreating && !$isStaff)
                            ->minDate(fn(callable $get) => $get('from_date') ? Carbon::parse($get('from_date'))->addDay() : Carbon::now()->addDay())
                            ->reactive()
                            ->afterStateUpdated(function (callable $set, callable $get) {
                                if ($get('from_date') && $get('back_to_work_date')) {
                                    $fromDate = $get('from_date');
                                    $backToWorkDate = $get('back_to_work_date');
                                    
                                    // Tanggal terakhir cuti = H-1 sebelum tanggal masuk kerja
                                    $toDate = Carbon::parse($backToWorkDate)->subDay()->format('Y-m-d');
                                    $set('to_date', $toDate);
                                    
                                    // Hitung hari kerja saja (tidak termasuk weekend dan hari libur)
                                    $workingDays = static::calculateWorkingDays($fromDate, $toDate);
                                    $set('days', $workingDays);
                                }
                            }),

                        Forms\Components\TextInput::make('days')
                            ->label('Jumlah Hari Libur')
                            ->helperText('Hanya hari kerja yang dihitung (tidak termasuk weekend dan hari libur)')
                            ->numeric()
                            ->disabled()
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
                            ->visible(fn() => $user->hasRole('manager') && !$isCreating)
                            ->reactive(),

                        Forms\Components\Toggle::make('approval_hrd')
                            ->label('Persetujuan HRD')
                            ->helperText('Setujui atau tolak permohonan cuti ini')
                            ->visible(fn() => $user->hasRole('hrd') && !$isCreating)
                            ->reactive(),

                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Alasan Penolakan')
                            ->maxLength(500)
                            ->visible(function (callable $get) use ($user, $isCreating) {
                                if ($isCreating) return false;

                                if ($user->hasRole('manager') && $get('approval_manager') === false) {
                                    return true;
                                }

                                if ($user->hasRole('hrd') && $get('approval_hrd') === false) {
                                    return true;
                                }

                                return false;
                            })
                            ->required(function (callable $get) use ($user) {
                                if ($user->hasRole('manager') && $get('approval_manager') === false) {
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
        $isStaff = $user->hasAnyRole(['staff', 'staff_keuangan']);

        return $table
            ->headerActions([
                ExportAction::make()
                    ->exporter(LeaveExporter::class)
                    ->visible(fn() => $user->hasRole('hrd')),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Nama Karyawan')
                    ->searchable()
                    ->sortable()
                    ->visible(!$isStaff),

                Tables\Columns\TextColumn::make('user.npp')
                    ->label('NPP')
                    ->searchable()
                    ->sortable()
                    ->visible(!$isStaff),

                Tables\Columns\TextColumn::make('user.division.name')
                    ->label('Divisi')
                    ->searchable()
                    ->sortable()
                    ->visible(!$isStaff),

                Tables\Columns\TextColumn::make('user.roles.name')
                    ->label('Jabatan')
                    ->formatStateUsing(fn($state) => $state ?: '-')
                    ->searchable()
                    ->sortable()
                    ->visible(!$isStaff),

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

                Tables\Columns\TextColumn::make('back_to_work_date')
                    ->label('Masuk Kembali')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('to_date')
                    ->label('Berakhir Cuti')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('days')
                    ->label('Hari Kerja')
                    ->alignCenter()
                    ->suffix(' hari'),

                Tables\Columns\TextColumn::make('user.leaveQuotas')
                    ->label('Sisa Cuti Tahunan')
                    ->alignCenter()
                    ->getStateUsing(function ($record) {
                        $quota = $record->user?->leaveQuotas?->first();
                        return $quota ? $quota->remaining_casual_quota . ' hari' : '0 hari';
                    })
                    ->sortable()
                    ->visible(!$isStaff),

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
                    })
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

        if ($user->hasAnyRole(['staff', 'staff_keuangan'])) {
            return parent::getEloquentQuery()->where('user_id', $user->id);
        }

        return parent::getEloquentQuery()->where('user_id', Auth::user()?->id);
    }
}