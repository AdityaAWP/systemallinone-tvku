<?php

namespace App\Filament\Resources;

use App\Exports\OvertimeYearlyExport;
use App\Filament\Resources\OvertimeResource\Pages;
use App\Models\Overtime;
use Barryvdh\DomPDF\Facade\Pdf as FacadePdf;
use Barryvdh\DomPDF\PDF;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Blade;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class OvertimeResource extends Resource
{
    protected static ?string $model = Overtime::class;
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationGroup = 'Menu Karyawan';
    protected static ?string $navigationLabel = 'Lembur';
    protected static ?string $label = 'Permohonan Lembur';
    protected static ?int $navigationSort = -1;

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

    public static function getNavigationBadge(): ?string
    {
        $user = Auth::user();

        if (static::isStaff($user)) {
            // Staff: jumlah lembur miliknya sendiri
            $count = Overtime::where('user_id', $user->id)->count();
            return $count > 0 ? (string) $count : null;
        }

        if ($user->hasRole('hrd')) {
            // HRD: jumlah semua data lembur
            $count = Overtime::count();
            return $count > 0 ? (string) $count : null;
        }

        if (static::isManager($user) || static::isKepala($user)) {
            // Manager & Kepala: jumlah lembur dari staff yang mereka manage (berdasarkan atasan relationship)
            $count = Overtime::whereHas('user', function ($query) use ($user) {
                $query->where('manager_id', $user->id)
                      ->orWhere('created_by', $user->id); // Include users created by this manager
            })->count();
            return $count > 0 ? (string) $count : null;
        }

        // Default: jumlah lembur miliknya sendiri
        $count = Overtime::where('user_id', $user->id)->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getNavigationBadge() > 0 ? 'primary' : null;
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        
        $query = parent::getEloquentQuery()->whereHas('user', function ($query) {
            $query->where('is_active', true);
        });

        if (static::isStaff($user) && !$user->hasRole('hrd') && !static::isManager($user) && !static::isKepala($user)) {
            // Logika ini tetap, karena $query sudah difilter di atas.
            return $query->where('user_id', $user->id);
        }

        // Untuk HRD, Manager, dan Kepala: tampilkan hanya data lembur TERAKHIR per karyawan.
        if ($user->hasRole('hrd') || static::isManager($user) || static::isKepala($user)) {

            // Langkah 1: Tentukan query untuk mendapatkan ID karyawan yang dapat diakses DAN AKTIF.
            $accessibleUsersQuery = \App\Models\User::query()
                ->where('is_active', true); // <-- TAMBAHKAN FILTER INI

            // Jika bukan HRD (yaitu manager/kepala), batasi hanya untuk staff mereka + diri sendiri.
            if (!$user->hasRole('hrd')) {
                $accessibleUsersQuery->where(function ($q) use ($user) {
                    $q->where('manager_id', $user->id)
                      ->orWhere('created_by', $user->id)
                      ->orWhere('id', $user->id); // Sertakan data mereka sendiri
                });
            }
            
            // Langkah 2: Buat subquery untuk mendapatkan ID dari entri lembur terakhir
            // untuk setiap karyawan yang dapat diakses.
            $latestOvertimeIdsSubquery = Overtime::selectRaw('MAX(id)')
                ->whereIn('user_id', $accessibleUsersQuery->select('id'))
                ->groupBy('user_id');

            // Langkah 3: Filter query utama untuk hanya menyertakan ID yang ditemukan.
            // Query utama sudah memiliki filter `whereHas` dari awal, jadi ini aman.
            return $query->whereIn('id', $latestOvertimeIdsSubquery);
        }

        // Fallback default: hanya bisa melihat lembur sendiri jika tidak ada peran yang cocok.
        return $query->where('user_id', $user->id);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(3)
                    ->schema([
                        DatePicker::make('tanggal_overtime')
                            ->label('Tanggal Lembur')
                            ->required(),

                        Select::make('is_holiday')
                            ->label('Status Hari')
                            ->options([
                                0 => 'Hari Kerja',
                                1 => 'Hari Libur'
                            ])
                            ->default(0)
                            ->required()
                            ->live()
                            ->columnSpan(1),

                        Forms\Components\Placeholder::make('spacer')
                            ->label('')
                            ->columnSpan(1),
                    ]),

                Forms\Components\Grid::make(2)
                    ->schema([
                        TimePicker::make('normal_work_time_check_in')
                            ->label('Waktu Mulai Kerja Normal')
                            ->required(fn(Get $get): bool => $get('is_holiday') == 0)
                            ->hidden(fn(Get $get): bool => $get('is_holiday') == 1)
                            ->default(fn () => Auth::user()->office_start_time)
                            ->seconds(false),
                        TimePicker::make('normal_work_time_check_out')
                            ->label('Waktu Selesai Kerja Normal')
                            ->required(fn(Get $get): bool => $get('is_holiday') == 0)
                            ->hidden(fn(Get $get): bool => $get('is_holiday') == 1)
                            ->default(fn () => Auth::user()->office_end_time)
                            ->seconds(false),
                    ])
                    ->hidden(fn(Get $get): bool => $get('is_holiday') == 1),

                Forms\Components\Grid::make(2)
                    ->schema([
                        TimePicker::make('check_in')
                            ->label('Waktu Mulai Lembur')
                            ->seconds(false)
                            ->required(),
                        TimePicker::make('check_out')
                            ->label('Waktu Selesai Lembur')
                            ->seconds(false)
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $tanggal = $get('tanggal_overtime');
                                $checkIn = $get('check_in');
                                $checkOut = $get('check_out');

                                if ($tanggal && $checkIn && $checkOut) {
                                    try {
                                        $tanggalString = Carbon::parse($tanggal)->format('Y-m-d');
                                        $checkInTime = Carbon::parse($checkIn)->format('H:i:s');
                                        $checkOutTime = Carbon::parse($checkOut)->format('H:i:s');

                                        $checkInDateTime = Carbon::parse("{$tanggalString} {$checkInTime}");
                                        $checkOutDateTime = Carbon::parse("{$tanggalString} {$checkOutTime}");

                                        if ($checkOutDateTime->lt($checkInDateTime)) {
                                            $checkOutDateTime->addDay();
                                        }

                                        $totalMinutes = abs($checkOutDateTime->diffInMinutes($checkInDateTime));

                                        $hours = (int)floor($totalMinutes / 60);
                                        $minutes = $totalMinutes % 60;

                                        $set('overtime', round($totalMinutes / 60, 2));
                                        $set('overtime_hours', $hours);
                                        $set('overtime_minutes', $minutes);

                                        Log::info("Form calculation - Date: {$tanggalString}, Check-in: {$checkInDateTime->format('Y-m-d H:i:s')}, Check-out: {$checkOutDateTime->format('Y-m-d H:i:s')}, Total minutes: {$totalMinutes}, Hours: {$hours}, Minutes: {$minutes}");
                                    } catch (\Exception $e) {
                                        Log::error("Error in afterStateUpdated: " . $e->getMessage());
                                    }
                                }
                            }),
                    ]),

                Forms\Components\Grid::make(4)
                    ->schema([
                        TextInput::make('overtime_hours')
                            ->label('Jam')
                            ->disabled()
                            ->numeric()
                            ->columnSpan(1),
                        TextInput::make('overtime_minutes')
                            ->label('Menit')
                            ->disabled()
                            ->numeric()
                            ->columnSpan(1),
                        TextInput::make('description')
                            ->label('Deskripsi')
                            ->required()
                            ->columnSpan(2),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(fn($record) => static::getUrl('view', ['record' => $record]))
            ->headerActions([
                // HRD filtering actions - similar to Leave resource
                Tables\Actions\Action::make('Semua Lembur Staff')
                    ->label('Semua Lembur Staff')
                    ->icon('heroicon-o-users')
                    ->visible(fn() => Auth::user()->hasRole('hrd') || static::isManager(Auth::user()) || static::isKepala(Auth::user()))
                    ->url(fn() => url()->current() . '?tableFilters[my_overtime][value]=false')
                    ->color(fn() => !request()->hasAny(['tableFilters']) || request()->input('tableFilters.my_overtime.value') === 'false' ? 'primary' : 'gray'),
                Tables\Actions\Action::make('Lembur Saya')
                    ->label('Lembur Saya')
                    ->icon('heroicon-o-user')
                    ->visible(fn() => Auth::user()->hasRole('hrd') || static::isManager(Auth::user()) || static::isKepala(Auth::user()))
                    ->url(fn() => url()->current() . '?tableFilters[my_overtime][value]=true')
                    ->color(fn() => request()->input('tableFilters.my_overtime.value') === 'true' ? 'primary' : 'gray'),
                Tables\Actions\Action::make('download_employee_overtime')
                    ->label('Download Lembur Karyawan')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('warning')
                    ->visible(fn() => Auth::user()->hasRole('hrd') || static::isManager(Auth::user()) || static::isKepala(Auth::user()))
                    ->form([
                        Forms\Components\Select::make('employee_id')
                            ->label('Pilih Karyawan')
                            ->placeholder('Pilih karyawan yang akan didownload')
                            ->options(function () {
                                $user = Auth::user();
                                
                                // Ambil user yang memiliki data lembur
                                $query = \App\Models\User::query()
                                    ->whereHas('overtimes'); // hanya user yang punya data lembur
                                
                                // Jika manager, hanya bisa lihat anak buahnya yang punya data lembur
                                if (static::isManager($user) && !$user->hasRole('hrd')) {
                                    $query->where('manager_id', $user->id);
                                }
                                
                                return $query->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make('month')
                            ->label('Bulan')
                            ->options([
                                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                            ])
                            ->default(Carbon::now()->month)
                            ->required(),
                        Forms\Components\TextInput::make('year')
                            ->label('Tahun')
                            ->numeric()
                            ->default(Carbon::now()->year)
                            ->minValue(2020)
                            ->maxValue(2030)
                            ->required(),
                        Forms\Components\Select::make('format')
                            ->label('Format Download')
                            ->options([
                                'pdf' => 'PDF',
                                'excel' => 'Excel',
                            ])
                            ->default('pdf')
                            ->visible(fn() => Auth::user()->hasRole('hrd'))
                            ->required(fn() => Auth::user()->hasRole('hrd')),
                    ])
                    ->action(function (array $data) {
                        $employeeId = $data['employee_id'];
                        $month = $data['month'];
                        $year = $data['year'];
                        $format = $data['format'] ?? 'pdf';

                        // Get employee info
                        $employee = \App\Models\User::find($employeeId);
                        
                        if (!$employee) {
                            \Filament\Notifications\Notification::make()
                                ->title('Error')
                                ->body('Karyawan tidak ditemukan.')
                                ->danger()
                                ->send();
                            return;
                        }

                        // Build query for specific user's overtime data
                        $query = Overtime::with(['user', 'user.division'])
                            ->where('user_id', $employeeId)
                            ->whereMonth('tanggal_overtime', $month)
                            ->whereYear('tanggal_overtime', $year);

                        $overtime = $query->orderBy('tanggal_overtime', 'asc')->get();

                        if ($overtime->isEmpty()) {
                            \Filament\Notifications\Notification::make()
                                ->title('Tidak ada data')
                                ->body('Tidak ada data lembur untuk karyawan dan periode yang dipilih.')
                                ->warning()
                                ->send();
                            return;
                        }

                        // Format nama bulan dalam bahasa Indonesia
                        $monthNames = [
                            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                        ];

                        $monthName = $monthNames[$month] . ' ' . $year;

                        if ($format === 'excel' && Auth::user()->hasRole('hrd')) {
                            // Export Excel
                            $filename = 'lembur-' . 
                                strtolower(str_replace([' ', '.'], '-', $employee->name)) . '-' .
                                strtolower(str_replace(' ', '-', $monthName)) . '.xlsx';
                            
                            return (new OvertimeYearlyExport($year, $employeeId))->download($filename);
                        } else {
                            // Export PDF
                            $title = 'Surat Permohonan Ijin Lembur - ' . $employee->name . ' - ' . $monthName;

                            $pdfData = [
                                'title' => $title,
                                'overtime' => $overtime,
                                'period' => $monthName,
                                'scope' => 'user_specific'
                            ];

                            $pdf = FacadePdf::loadview('overtimePDF', $pdfData);

                            $filename = 'surat-lembur-' . 
                                strtolower(str_replace([' ', '.'], '-', $employee->name)) . '-' .
                                strtolower(str_replace(' ', '-', $monthName)) . '.pdf';

                            return response()->streamDownload(function () use ($pdf) {
                                echo $pdf->output();
                            }, $filename, [
                                'Content-Type' => 'application/pdf',
                            ]);
                        }
                    }),
                Tables\Actions\Action::make('downloadMonthly')
                    ->label('Download Bulanan')
                    ->icon('heroicon-o-calendar')
                    ->color('green')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('month')
                                    ->label('Bulan')
                                    ->options([
                                        1 => 'Januari',
                                        2 => 'Februari',
                                        3 => 'Maret',
                                        4 => 'April',
                                        5 => 'Mei',
                                        6 => 'Juni',
                                        7 => 'Juli',
                                        8 => 'Agustus',
                                        9 => 'September',
                                        10 => 'Oktober',
                                        11 => 'November',
                                        12 => 'Desember',
                                    ])
                                    ->default(Carbon::now()->month)
                                    ->required(),

                                Forms\Components\TextInput::make('year')
                                    ->label('Tahun')
                                    ->numeric()
                                    ->default(Carbon::now()->year)
                                    ->minValue(2020)
                                    ->maxValue(2030)
                                    ->required(),

                                Forms\Components\Select::make('download_scope')
                                    ->label('Jenis Download')
                                    ->options(function () {
                                        $user = Auth::user();
                                        $options = [];

                                        if (static::isStaff($user)) {
                                            $options['my_data'] = 'Data Lembur Saya';
                                        } elseif ($user->hasRole('hrd')) {
                                            $options['my_data'] = 'Data Lembur Saya';
                                        } elseif (static::isManager($user) || static::isKepala($user)) {
                                            $options['my_data'] = 'Data Lembur Saya';
                                        } else {
                                            $options['my_data'] = 'Data Lembur Saya';
                                        }

                                        return $options;
                                    })
                                    ->default('my_data')
                                    ->required(),
                            ])
                    ])
                    ->action(function (array $data) {
                        $user = Auth::user();
                        $month = $data['month'];
                        $year = $data['year'];
                        $scope = $data['download_scope'];

                        // Build query berdasarkan scope dan role
                        $query = Overtime::with(['user', 'user.division'])
                            ->whereMonth('tanggal_overtime', $month)
                            ->whereYear('tanggal_overtime', $year);

                        if ($scope === 'my_data') {
                            // Data user sendiri
                            $query->where('user_id', $user->id);
                        } elseif ($scope === 'all_data' && $user->hasRole('hrd')) {
                            // Semua data untuk HRD
                            // Query sudah mencakup semua data
                        } elseif ($scope === 'staff_data' && (static::isManager($user) || static::isKepala($user))) {
                            // Data staff yang dikelola berdasarkan atasan relationship
                            $query->whereHas('user', function ($q) use ($user) {
                                $q->where('manager_id', $user->id)
                                  ->orWhere('created_by', $user->id);
                            });
                        } else {
                            // Fallback ke data sendiri jika scope tidak valid
                            $query->where('user_id', $user->id);
                        }

                        $overtime = $query->orderBy('tanggal_overtime', 'asc')->get();

                        if ($overtime->isEmpty()) {
                            \Filament\Notifications\Notification::make()
                                ->title('Tidak ada data')
                                ->body('Tidak ada data lembur untuk periode yang dipilih.')
                                ->warning()
                                ->send();
                            return;
                        }

                        // Format nama bulan dalam bahasa Indonesia
                        $monthNames = [
                            1 => 'Januari',
                            2 => 'Februari',
                            3 => 'Maret',
                            4 => 'April',
                            5 => 'Mei',
                            6 => 'Juni',
                            7 => 'Juli',
                            8 => 'Agustus',
                            9 => 'September',
                            10 => 'Oktober',
                            11 => 'November',
                            12 => 'Desember'
                        ];

                        $monthName = $monthNames[$month] . ' ' . $year;

                        // Tentukan title berdasarkan scope
                        $title = 'Surat Permohonan Ijin Lembur';
                        if ($scope === 'all_data') {
                            $title .= ' - Semua Staff';
                        } elseif ($scope === 'staff_data') {
                            $title .= ' - Staff ' . $user->name;
                        } else {
                            $title .= ' - ' . $user->name;
                        }
                        $title .= ' - ' . $monthName;

                        $data = [
                            'title' => $title,
                            'overtime' => $overtime,
                            'period' => $monthName,
                            'scope' => $scope
                        ];

                        $pdf = FacadePdf::loadview('overtimePDF', $data);

                        // Generate filename
                        $filename = 'surat-lembur-';
                        if ($scope === 'all_data') {
                            $filename .= 'semua-staff-';
                        } elseif ($scope === 'staff_data') {
                            $filename .= 'staff-' . strtolower(str_replace([' ', '.'], '-', $user->name)) . '-';
                        } else {
                            $filename .= strtolower(str_replace([' ', '.'], '-', $user->name)) . '-';
                        }
                        $filename .= strtolower(str_replace(' ', '-', $monthName)) . '.pdf';

                        return response()->streamDownload(function () use ($pdf) {
                            echo $pdf->output();
                        }, $filename, [
                            'Content-Type' => 'application/pdf',
                        ]);
                    }),
            ])
            ->columns([
                // Add employee information columns for HRD view
                TextColumn::make('user.name')
                    ->label('Nama Karyawan')
                    ->searchable()
                    ->sortable()
                    ->visible(fn() => Auth::user()->hasRole('hrd') || static::isManager(Auth::user()) || static::isKepala(Auth::user())),

                TextColumn::make('user.npp')
                    ->label('NPP')
                    ->searchable()
                    ->sortable()
                    ->visible(fn() => Auth::user()->hasRole('hrd') || static::isManager(Auth::user()) || static::isKepala(Auth::user())),

                TextColumn::make('user.division.name')
                    ->label('Divisi')
                    ->searchable()
                    ->sortable()
                    ->visible(fn() => Auth::user()->hasRole('hrd') || static::isManager(Auth::user()) || static::isKepala(Auth::user())),

                TextColumn::make('user.position')
                    ->label('Jabatan')
                    ->formatStateUsing(fn($state) => $state ?: '-')
                    ->searchable()
                    ->sortable()
                    ->visible(fn() => Auth::user()->hasRole('hrd') || static::isManager(Auth::user()) || static::isKepala(Auth::user())),

                // Add manager information column to show who is the staff's manager
                TextColumn::make('user.manager.name')
                    ->label('Atasan')
                    ->formatStateUsing(fn($state) => $state ?: 'Tidak ada')
                    ->searchable()
                    ->sortable()
                    ->visible(fn() => Auth::user()->hasRole('hrd')),

                TextColumn::make('tanggal_overtime')
                    ->label('Tanggal Lembur')
                    ->searchable()
                    ->date('d F Y')
                    ->sortable(),
                TextColumn::make('is_holiday')
                    ->label('Status Hari')
                    ->formatStateUsing(fn(string $state): string => $state ? 'Hari Libur' : 'Hari Kerja')
                    ->badge()
                    ->color(fn(string $state): string => $state ? 'success' : 'primary'),
                TextColumn::make('normal_work_time_check_in')
                    ->label('Waktu Mulai Kerja')
                    ->searchable()
                    ->dateTime('H:i')
                    ->placeholder('Hari Libur')
                    ->hidden(fn($record) => $record?->is_holiday),
                TextColumn::make('normal_work_time_check_out')
                    ->label('Waktu Selesai Kerja')
                    ->searchable()
                    ->dateTime('H:i')
                    ->placeholder('Hari Libur')
                    ->hidden(fn($record) => $record?->is_holiday),
                TextColumn::make('check_in')
                    ->label('Waktu Mulai Lembur')
                    ->searchable()
                    ->dateTime('H:i'),
                TextColumn::make('check_out')
                    ->label('Waktu Selesai Lembur')
                    ->searchable()
                    ->dateTime('H:i'),
                TextColumn::make('overtime_formatted')
                    ->searchable()
                    ->label('Durasi Lembur')
                    ->state(fn(Overtime $record): string => "{$record->overtime_hours} jam {$record->overtime_minutes} menit"),
                TextColumn::make('description')
                    ->searchable()
                    ->label('Deskripsi'),
                TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->visible(fn() => Auth::user()->hasRole('hrd') || static::isManager(Auth::user()) || static::isKepala(Auth::user())),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_holiday')
                    ->label('Status Hari')
                    ->options([
                        0 => 'Hari Kerja',
                        1 => 'Hari Libur'
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
                                fn(Builder $query, $date): Builder => $query->whereDate('tanggal_overtime', '>=', $date),
                            )
                            ->when(
                                $data['to'],
                                fn(Builder $query, $date): Builder => $query->whereDate('tanggal_overtime', '<=', $date),
                            );
                    }),

                Tables\Filters\TernaryFilter::make('my_overtime')
                    ->label('Tampilkan Lembur Saya')
                    ->visible(fn() => Auth::user()->hasRole('hrd') || static::isManager(Auth::user()) || static::isKepala(Auth::user()))
                    ->trueLabel('Lembur Saya')
                    ->falseLabel('Semua Lembur Staff')
                    ->queries(
                        true: fn(Builder $query) => $query->where('user_id', Auth::id()),
                        false: fn(Builder $query) => $query,
                        blank: fn(Builder $query) => $query,
                    ),

                // Add filter for managers to see only their staff's overtime
                Tables\Filters\TernaryFilter::make('my_staff_overtime')
                    ->label('Lembur Staff Saya')
                    ->visible(fn() => static::isManager(Auth::user()) || static::isKepala(Auth::user()))
                    ->trueLabel('Hanya Staff Saya')
                    ->falseLabel('Semua yang Bisa Diakses')
                    ->queries(
                        true: fn(Builder $query) => $query->whereHas('user', function ($q) {
                            $q->where('manager_id', Auth::id())
                              ->orWhere('created_by', Auth::id());
                        }),
                        false: fn(Builder $query) => $query,
                        blank: fn(Builder $query) => $query,
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(
                        fn($record) => $record->user_id === Auth::id()
                    ),
                Tables\Actions\DeleteAction::make()
                    ->visible(
                        fn($record) => $record->user_id === Auth::id()
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Bulk delete dihapus karena tidak ada yang boleh melakukan bulk delete
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
            'index' => Pages\ListOvertimes::route('/'),
            'create' => Pages\CreateOvertime::route('/create'),
            'edit' => Pages\EditOvertime::route('/{record}/edit'),
            'view' => Pages\ViewOvertime::route('/{record}'),
        ];
    }
}