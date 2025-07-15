<?php

namespace App\Filament\Resources;

use App\Filament\Exports\DailyReportExporter;
use App\Filament\Resources\DailyReportResource\Pages;
use App\Models\DailyReport;
use App\Exports\DailyReportExcel;
use Filament\Actions\Exports\Models\Export;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ExportBulkAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Auth;


class DailyReportResource extends Resource
{
    protected static ?string $model = DailyReport::class;
    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';
    protected static ?string $navigationGroup = 'Menu Karyawan';
    protected static ?string $label = 'Laporan Harian';
    protected static ?int $navigationSort = 1;

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

    /**
     * Check if user has any kepala role
     */
    private static function isKepala($user): bool
    {
        return $user->roles()->where('name', 'like', 'kepala%')->exists();
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        $query = parent::getEloquentQuery();

        // Jika pengguna adalah staff biasa (bukan manager/hrd/kepala),
        // mereka akan melihat semua laporan harian mereka sendiri.
        if (static::isStaff($user) && !$user->hasRole('hrd') && !static::isManager($user) && !static::isKepala($user)) {
            return $query->where('user_id', $user->id);
        }

        // Untuk HRD, Manager, dan Kepala: tampilkan hanya data laporan TERAKHIR per karyawan.
        if ($user->hasRole('hrd') || static::isManager($user) || static::isKepala($user)) {

            // Langkah 1: Tentukan query untuk mendapatkan ID karyawan yang dapat diakses.
            $accessibleUsersQuery = \App\Models\User::query();

            // Jika bukan HRD (yaitu manager/kepala), batasi hanya untuk staff di divisi mereka + diri sendiri.
            if (!$user->hasRole('hrd')) {
                $userDivisionIds = $user->divisions()->pluck('divisions.id')->toArray();
                if (empty($userDivisionIds) && $user->division_id) {
                    $userDivisionIds = [$user->division_id];
                }

                if (!empty($userDivisionIds)) {
                    // Filter untuk user yang ada di divisi yang dikelola ATAU user itu sendiri
                    $accessibleUsersQuery->where(function ($q) use ($userDivisionIds, $user) {
                        $q->whereIn('division_id', $userDivisionIds)
                          ->orWhere('id', $user->id); // Sertakan laporan manager itu sendiri
                    });
                } else {
                    // Jika manager tidak mengelola divisi apapun, hanya tampilkan laporannya sendiri
                    $accessibleUsersQuery->where('id', $user->id);
                }
            }
            
            // Langkah 2: Buat subquery untuk mendapatkan ID dari entri laporan terakhir
            // untuk setiap karyawan yang dapat diakses.
            $latestReportIdsSubquery = DailyReport::selectRaw('MAX(id)')
                ->whereIn('user_id', $accessibleUsersQuery->select('id'))
                ->groupBy('user_id');

            // Langkah 3: Filter query utama untuk hanya menyertakan ID yang ditemukan.
            // Ini akan secara efektif menampilkan hanya satu baris (yang terbaru) per karyawan.
            return $query->whereIn('id', $latestReportIdsSubquery);
        }

        // Fallback default: hanya bisa melihat laporan sendiri jika tidak ada peran yang cocok.
        return $query->where('user_id', $user->id);
    }

    public static function getNavigationBadge(): ?string
    {
        $user = Auth::user();

        if (static::isStaff($user)) {
            // Staff: jumlah lembur miliknya sendiri
            $count = DailyReport::where('user_id', $user->id)->count();
            return $count > 0 ? (string) $count : null;
        }

        if ($user->hasRole('hrd')) {
            // HRD: jumlah semua data lembur
            $count = DailyReport::count();
            return $count > 0 ? (string) $count : null;
        }

        if (static::isManager($user) || static::isKepala($user)) {
            // Manager & Kepala: jumlah lembur di divisinya (semua divisi yang dikelola)
            $userDivisionIds = $user->divisions()->pluck('divisions.id')->toArray();

            // Jika tidak ada divisi dari many-to-many, fallback ke primary division
            if (empty($userDivisionIds) && $user->division_id) {
                $userDivisionIds = [$user->division_id];
            }

            $count = DailyReport::whereHas('user', function ($query) use ($userDivisionIds) {
                $query->whereIn('division_id', $userDivisionIds);
            })->count();
            return $count > 0 ? (string) $count : null;
        }

        // Default: jumlah lembur miliknya sendiri
        $count = DailyReport::where('user_id', $user->id)->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(3)
                    ->schema([
                        DatePicker::make('entry_date')
                            ->label('Tanggal Kerja')
                            ->required(),
                        TimePicker::make('check_in')
                            ->label('Waktu Mulai Bekerja')
                            ->seconds(false)
                            ->required(),
                        TimePicker::make('check_out')
                            ->label('Waktu Berakhir Bekerja')
                            ->seconds(false)
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $tanggal = $get('entry_date');
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

                                        $set('work_hours', round($totalMinutes / 60, 2));
                                        $set('work_hours_component', $hours);
                                        $set('work_minutes_component', $minutes);

                                        Log::info("Form calculation - Date: {$tanggalString}, Check-in: {$checkInDateTime->format('Y-m-d H:i:s')}, Check-out: {$checkOutDateTime->format('Y-m-d H:i:s')}, Total minutes: {$totalMinutes}, Hours: {$hours}, Minutes: {$minutes}");
                                    } catch (\Exception $e) {
                                        Log::error("Error in afterStateUpdated: " . $e->getMessage());
                                    }
                                }
                            }),
                    ]),

                Forms\Components\Grid::make(4)
                    ->schema([
                        TextInput::make('work_hours_component')
                            ->label('Jam')
                            ->disabled()
                            ->numeric()
                            ->columnSpan(1),
                        TextInput::make('work_minutes_component')
                            ->label('Menit')
                            ->disabled()
                            ->numeric()
                            ->columnSpan(1),
                        RichEditor::make('description')
                            ->label('Deskripsi')
                            ->required()
                            ->columnSpan(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(fn($record) => static::getUrl('view', ['record' => $record]))
            ->headerActions([
                Tables\Actions\Action::make('reset_to_all')
                    ->label('Semua Laporan Staff')
                    ->icon('heroicon-o-users')
                    ->visible(fn() => Auth::user()->hasRole('hrd') || static::isManager(Auth::user()) || static::isKepala(Auth::user()))
                    ->url(fn() => request()->url())
                    ->color(fn() => !request()->hasAny(['tableFilters']) || request()->input('tableFilters.my_reports.value') === 'false' ? 'primary' : 'gray'),

                Tables\Actions\Action::make('filter_my_reports')
                    ->label('Laporan Saya')
                    ->icon('heroicon-o-user')
                    ->visible(fn() => Auth::user()->hasRole('hrd') || static::isManager(Auth::user()) || static::isKepala(Auth::user()))
                    ->url(fn() => request()->url() . '?tableFilters[my_reports][value]=true')
                    ->color(fn() => request()->input('tableFilters.my_reports.value') === 'true' ? 'primary' : 'gray'),

                Action::make('download_employee_dailyreport')
                    ->label('Download Laporan Karyawan')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('warning')
                    ->visible(fn() => Auth::user()->hasRole('hrd') || static::isManager(Auth::user()) || static::isKepala(Auth::user()))
                    ->form([
                        Select::make('employee_id')
                            ->label('Pilih Karyawan')
                            ->placeholder('Pilih karyawan yang akan didownload')
                            ->options(function () {
                                $user = Auth::user();
                                
                                // Ambil user yang memiliki data daily report
                                $query = \App\Models\User::query()
                                    ->whereHas('dailyReports'); // hanya user yang punya data daily report
                                
                                // Jika manager, hanya bisa lihat anak buahnya yang punya data daily report
                                if (static::isManager($user) && !$user->hasRole('hrd')) {
                                    $query->where('manager_id', $user->id);
                                }
                                
                                return $query->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->required(),
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
                    ->action(function (array $data) {
                        $employeeId = $data['employee_id'];
                        $year = $data['year'];

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

                        // Check if employee has daily reports
                        $reportCount = DailyReport::where('user_id', $employeeId)
                            ->whereYear('entry_date', $year)
                            ->count();

                        if ($reportCount === 0) {
                            \Filament\Notifications\Notification::make()
                                ->title('Tidak ada data')
                                ->body('Tidak ada data laporan harian untuk karyawan dan tahun yang dipilih.')
                                ->warning()
                                ->send();
                            return;
                        }

                        // Export Excel (Daily Report tidak ada PDF)
                        $userName = str_replace(' ', '_', $employee->name);
                        $filename = "laporan_harian_{$userName}_{$year}.xlsx";
                        return (new DailyReportExcel($year, $employeeId))->download($filename);
                    }),

                Action::make('export_monthly_excel')
                    ->label('Ekspor Excel')
                    ->color('success')
                    ->icon('heroicon-o-document-arrow-down')
                    ->form([
                        Select::make('year')
                            ->label('Tahun')
                            ->options(function () {
                                $years = [];
                                $currentYear = now()->year;
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
                            ->options([
                                'personal' => 'Data Pribadi Saya',
                                'all' => 'Semua Data Staff'
                            ])
                            ->default('personal')
                            ->visible(fn() => Auth::user()->hasRole('hrd') ||
                                DailyReportResource::isManager(Auth::user()) ||
                                DailyReportResource::isKepala(Auth::user()))
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $year = $data['year'];
                        $user = Auth::user();

                        // Jika HRD, Manager, atau Kepala dan pilih "all", export semua data staff/divisi
                        if (
                            ($user->hasRole('hrd') || DailyReportResource::isManager($user) || DailyReportResource::isKepala($user))
                            && isset($data['export_type']) && $data['export_type'] === 'all'
                        ) {
                            // Untuk HRD: semua data staff
                            // Untuk Manager/Kepala: semua data staff di divisi yang dikelola
                            $userId = null;
                            $filename = "laporan_harian_semua_staff_{$year}.xlsx";
                            // Untuk Manager/Kepala, filter di dalam DailyReportExcel jika perlu
                            return (new DailyReportExcel($year, $userId))->download($filename);
                        } else {
                            // Untuk staff, manager, kepala, atau HRD yang pilih "personal"
                            $userId = $user->id;
                            $userName = str_replace(' ', '_', $user->name);
                            $filename = "laporan_harian_{$userName}_{$year}.xlsx";
                            return (new DailyReportExcel($year, $userId))->download($filename);
                        }
                    })
                    ->visible(fn() => Auth::user()->hasRole('hrd')
                        || DailyReportResource::isStaff(Auth::user())
                        || DailyReportResource::isManager(Auth::user())
                        || DailyReportResource::isKepala(Auth::user())),
            ])
            ->columns([
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
                TextColumn::make('entry_date')
                    ->label('Tanggal')
                    ->searchable()
                    ->date('d F Y')
                    ->sortable(),
                TextColumn::make('check_in')
                    ->label('Waktu Mulai Bekerja')
                    ->searchable()
                    ->dateTime('H:i'),
                TextColumn::make('check_out')
                    ->label('Waktu Selesai Bekerja')
                    ->searchable()
                    ->dateTime('H:i'),
                TextColumn::make('hours_formatted')
                    ->searchable()
                    ->label('Durasi Kerja')
                    ->state(fn(DailyReport $record): string => "{$record->work_hours_component} jam {$record->work_minutes_component} menit"),
                TextColumn::make('description')
                    ->searchable()
                    ->formatStateUsing(fn(string $state): HtmlString => new HtmlString($state))
                    ->label('Deskripsi'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('my_reports')
                    ->label('Tampilkan Laporan Saya')
                    ->visible(fn() => Auth::user()->hasRole('hrd') || static::isManager(Auth::user()) || static::isKepala(Auth::user()))
                    ->trueLabel('Laporan Saya')
                    ->falseLabel('Semua Laporan Staff')
                    ->queries(
                        true: fn(Builder $query) => $query->where('user_id', Auth::id()),
                        false: fn(Builder $query) => $query,
                        blank: fn(Builder $query) => $query,
                    ),
                Tables\Filters\SelectFilter::make('divisi')
                    ->label('Filter Divisi')
                    ->options(function () {
                        return \App\Models\Division::orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            $query->whereHas('user', function ($q) use ($data) {
                                $q->where('division_id', $data['value']);
                            });
                        }
                    })
                    ->searchable()
                    ->preload()
                    ->visible(fn() => Auth::user()->hasRole('hrd') || static::isManager(Auth::user()) || static::isKepala(Auth::user())),
                Tables\Filters\SelectFilter::make('user')
                    ->label('Filter Karyawan')
                    ->options(function () {
                        return \App\Models\User::whereHas('dailyReports')
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            $query->where('user_id', $data['value']);
                        }
                    })
                    ->searchable()
                    ->preload()
                    ->visible(fn() => Auth::user()->hasRole('hrd') || static::isManager(Auth::user()) || static::isKepala(Auth::user())),
                Tables\Filters\SelectFilter::make('bulan')
                    ->label('Bulan')
                    ->options(function () {
                        $months = [];
                        $query = DailyReport::query();

                        // Apply same query logic as getEloquentQuery
                        $user = Auth::user();
                        if (!$user->hasRole('hrd')) {
                            if (static::isManager($user) || static::isKepala($user)) {
                                $userDivisionIds = $user->divisions()->pluck('divisions.id')->toArray();

                                if (empty($userDivisionIds) && $user->division_id) {
                                    $userDivisionIds = [$user->division_id];
                                }

                                if (!empty($userDivisionIds)) {
                                    $query->whereHas('user', function ($q) use ($userDivisionIds) {
                                        $q->whereIn('division_id', $userDivisionIds);
                                    });
                                } else {
                                    $query->where('user_id', $user->id);
                                }
                            } else {
                                $query->where('user_id', $user->id);
                            }
                        }

                        $reports = $query->selectRaw('DISTINCT DATE_FORMAT(entry_date, "%Y-%m") as month')
                            ->orderBy('month', 'desc')
                            ->get();

                        foreach ($reports as $report) {
                            $date = Carbon::createFromFormat('Y-m', $report->month);
                            $months[$report->month] = $date->translatedFormat('F Y');
                        }

                        return $months;
                    })
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            $query->whereYear('entry_date', Carbon::parse($data['value'])->year)
                                ->whereMonth('entry_date', Carbon::parse($data['value'])->month);
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Lihat'),
                Tables\Actions\EditAction::make()
                    ->label('Edit')
                    ->visible(
                        fn($record) => $record->user_id === Auth::id()
                    ),
                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->visible(
                        fn($record) => $record->user_id === Auth::id()
                    ),
                Tables\Actions\Action::make('export_individual')
                    ->label('Export Data')
                    ->icon('heroicon-o-document-text')
                    ->color('success')
                    ->tooltip('Export laporan harian karyawan ini')
                    ->visible(fn() => Auth::user()->hasRole('hrd') || static::isManager(Auth::user()) || static::isKepala(Auth::user()))
                    ->modalHeading(fn(DailyReport $record) => 'Export Laporan - ' . $record->user->name)
                    ->modalDescription('Export laporan harian karyawan ini')
                    ->modalWidth('md')
                    ->form([
                        TextInput::make('user_info')
                            ->label('Karyawan')
                            ->disabled()
                            ->default(fn(DailyReport $record) => $record->user->name . ' (' . ($record->user->npp ?? 'No NPP') . ')'),
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
                    ->action(function (DailyReport $record, array $data) {
                        $user = $record->user;
                        $year = $data['year'];
                        $userName = str_replace(' ', '_', $user->name);

                        $filename = "laporan_harian_{$userName}_{$year}.xlsx";
                        return (new DailyReportExcel($year, $user->id))->download($filename);
                    }),

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Bulk delete dihapus karena tidak ada yang boleh melakukan bulk delete
                    ExportBulkAction::make()
                        ->exporter(DailyReportExporter::class)
                        ->visible(fn() => Auth::user()->hasRole('hrd') || static::isManager(Auth::user()) || static::isKepala(Auth::user()))

                ]),
            ]);
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
            'index' => Pages\ListDailyReports::route('/'),
            'create' => Pages\CreateDailyReport::route('/create'),
            'edit' => Pages\EditDailyReport::route('/{record}/edit'),
            'view' => Pages\ViewDailyReport::route('/{record}'),
        ];
    }
}
