<?php

namespace App\Filament\Resources;

use App\Filament\Exports\DailyReportExporter;
use App\Filament\Resources\DailyReportResource\Pages;
use App\Models\DailyReport;
use App\Models\UploadedFile;
use App\Exports\DailyReportExcel;
use App\Imports\DailyReportImport;
use Filament\Actions\Exports\Models\Export;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
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
use Maatwebsite\Excel\Facades\Excel;
use Filament\Notifications\Notification;

class DailyReportResource extends Resource
{
    protected static ?string $model = DailyReport::class;
    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';
    protected static ?string $navigationGroup = 'Menu Karyawan';
    protected static ?string $label = 'Laporan Harian';
    protected static ?int $navigationSort = 1;

    private static function isStaff($user): bool
    {
        return $user->roles()->where('name', 'like', 'staff%')->exists();
    }

    private static function isManager($user): bool
    {
        return $user->roles()->where('name', 'like', 'manager%')->exists();
    }

    private static function isKepala($user): bool
    {
        return $user->roles()->where('name', 'like', 'kepala%')->exists();
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();

        $query = parent::getEloquentQuery()->whereHas('user', function ($query) {
            $query->where('is_active', true);
        });

        if (static::isStaff($user) && !$user->hasRole('hrd') && !static::isManager($user) && !static::isKepala($user)) {
            return $query->where('user_id', $user->id);
        }

        if ($user->hasRole('hrd') || static::isManager($user) || static::isKepala($user)) {

            $accessibleUsersQuery = \App\Models\User::query()
                ->where('is_active', true);

            if (!$user->hasRole('hrd')) {
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

            $latestReportIdsSubquery = DailyReport::selectRaw('MAX(id)')
                ->whereIn('user_id', $accessibleUsersQuery->select('id'))
                ->groupBy('user_id');

            return $query->whereIn('id', $latestReportIdsSubquery);
        }

        return $query->where('user_id', $user->id);
    }

    public static function getNavigationBadge(): ?string
    {
        $user = Auth::user();

        if (static::isStaff($user)) {
            $count = DailyReport::where('user_id', $user->id)->count();
            return $count > 0 ? (string) $count : null;
        }

        if ($user->hasRole('hrd')) {
            $count = DailyReport::count();
            return $count > 0 ? (string) $count : null;
        }

        if (static::isManager($user) || static::isKepala($user)) {
            $userDivisionIds = $user->divisions()->pluck('divisions.id')->toArray();

            if (empty($userDivisionIds) && $user->division_id) {
                $userDivisionIds = [$user->division_id];
            }

            $count = DailyReport::whereHas('user', function ($query) use ($userDivisionIds) {
                $query->whereIn('division_id', $userDivisionIds);
            })->count();
            return $count > 0 ? (string) $count : null;
        }

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
                            ->required()
                            ->default(fn() => Auth::user()->office_start_time),
                        TimePicker::make('check_out')
                            ->label('Waktu Berakhir Bekerja')
                            ->seconds(false)
                            ->required()
                            ->default(fn() => Auth::user()->office_end_time)
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
                        RichEditor::make('description')
                            ->label('Deskripsi')
                            ->required()
                            ->columnSpan(4),
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

                // Import Excel Action
                Action::make('import_excel')
                    ->label('Import Excel')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('info')
                    //->visible(fn() => Auth::user()->hasRole('hrd') || static::isManager(Auth::user()) || static::isKepala(Auth::user()))
                    ->form([
                        FileUpload::make('excel_file')
                            ->label('File Excel')
                            ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel', '.xlsx', '.xls'])
                            ->required()
                            ->maxSize(10240) // 10MB
                            ->helperText('Format yang didukung: .xlsx, .xls (Maksimal 10MB)')
                            ->directory('temp/imports') // Folder temporary untuk import
                            ->disk('public'), // Menggunakan disk public untuk temporary file
                    ])
                    ->action(function (array $data) {
                        try {
                            $filePath = $data['excel_file'];

                            if (!$filePath) {
                                throw new \Exception('File tidak ditemukan');
                            }

                            // Dapatkan path lengkap file
                            $fullPath = storage_path('app/public/' . $filePath);

                            // Cek apakah file benar-benar ada
                            if (!file_exists($fullPath)) {
                                throw new \Exception('File tidak ditemukan di path: ' . $fullPath);
                            }

                            Log::info("Import file path: " . $fullPath);

                            // Import data menggunakan Laravel Excel
                            $import = new DailyReportImport();
                            Excel::import($import, $fullPath);

                            // Hitung jumlah data yang berhasil diimport
                            $importedCount = $import->getImportedCount();
                            $skippedCount = $import->getSkippedCount();
                            $errorCount = $import->getErrorCount();

                            // Hapus file temporary setelah import selesai
                            if (\Storage::disk('public')->exists($filePath)) {
                                \Storage::disk('public')->delete($filePath);
                                Log::info("Temporary file deleted: " . $filePath);
                            }

                            // Buat pesan notifikasi berdasarkan hasil import
                            $message = "Import selesai! ";
                            if ($importedCount > 0) {
                                $message .= "Berhasil mengimport {$importedCount} data. ";
                            }
                            if ($skippedCount > 0) {
                                $message .= "{$skippedCount} data dilewati (sudah ada). ";
                            }
                            if ($errorCount > 0) {
                                $message .= "{$errorCount} data gagal diimport.";
                            }

                            Notification::make()
                                ->title('Import Berhasil')
                                ->body($message)
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Log::error('Import Excel Error: ' . $e->getMessage());
                            Log::error('Stack trace: ' . $e->getTraceAsString());

                            // Hapus file temporary jika terjadi error
                            if (isset($filePath) && \Storage::disk('public')->exists($filePath)) {
                                \Storage::disk('public')->delete($filePath);
                            }

                            Notification::make()
                                ->title('Import Gagal')
                                ->body('Terjadi kesalahan saat import: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                // Upload File Action
                Action::make('upload_file')
                    ->label('Upload File')
                    ->icon('heroicon-o-document-arrow-up')
                    ->color('indigo')
                    //->visible(fn() => Auth::user()->hasRole('hrd') || static::isManager(Auth::user()) || static::isKepala(Auth::user()))
                    ->form([
                        FileUpload::make('uploaded_file')
                            ->label('File')
                            ->required()
                            ->maxSize(20480) // 20MB
                            ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png', 'text/plain'])
                            ->helperText('Format yang didukung: PDF, DOC, DOCX, JPG, PNG, TXT (Maksimal 20MB)')
                            ->directory('uploads/daily-reports')
                            ->storeFileNamesIn('original_filename'),
                        Forms\Components\TextInput::make('title')
                            ->label('Judul File')
                            ->required()
                            ->placeholder('Masukkan judul file...'),
                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->placeholder('Masukkan deskripsi file...')
                            ->rows(3),
                    ])
                    ->action(function (array $data) {
                        try {
                            $filePath = $data['uploaded_file'];

                            if (!$filePath) {
                                throw new \Exception('File tidak ditemukan');
                            }

                            // Simpan informasi file yang diupload
                            UploadedFile::create([
                                'filename' => basename($filePath),
                                'original_filename' => $data['original_filename'] ?? basename($filePath),
                                'file_path' => $filePath,
                                'file_type' => 'daily_report_document',
                                'file_size' => Storage::disk('public')->size($filePath),
                                'uploaded_by' => Auth::id(),
                                'title' => $data['title'],
                                'description' => $data['description'] ?? '',
                                'status' => 'completed'
                            ]);

                            Notification::make()
                                ->title('Upload Berhasil')
                                ->body('File berhasil diupload')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Log::error('Upload File Error: ' . $e->getMessage());

                            Notification::make()
                                ->title('Upload Gagal')
                                ->body('Terjadi kesalahan saat upload: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

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

                                $query = \App\Models\User::query()
                                    ->whereHas('dailyReports');

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

                        $employee = \App\Models\User::find($employeeId);

                        if (!$employee) {
                            \Filament\Notifications\Notification::make()
                                ->title('Error')
                                ->body('Karyawan tidak ditemukan.')
                                ->danger()
                                ->send();
                            return;
                        }

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

                        if (
                            ($user->hasRole('hrd') || DailyReportResource::isManager($user) || DailyReportResource::isKepala($user))
                            && isset($data['export_type']) && $data['export_type'] === 'all'
                        ) {
                            $userId = null;
                            $filename = "laporan_harian_semua_staff_{$year}.xlsx";
                            return (new DailyReportExcel($year, $userId))->download($filename);
                        } else {
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
                    ExportBulkAction::make()
                        ->exporter(DailyReportExporter::class)
                        ->visible(fn() => Auth::user()->hasRole('hrd') || static::isManager(Auth::user()) || static::isKepala(Auth::user()))
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
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
