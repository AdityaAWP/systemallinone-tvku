<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\Division;
use App\Models\User;
use App\Models\Overtime;
use App\Models\DailyReport;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Exports\OvertimeYearlyExport;
use App\Exports\DailyReportExcel;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Manajemen Karyawan';
    protected static ?string $navigationLabel = 'Data Karyawan';
    protected static ?string $label = 'Data Karyawan';
    protected static ?int $navigationSort = 4;

    public static function getNavigationBadge(): ?string
    {
        $userCount = User::count();
        return $userCount > 0 ? (string) $userCount : null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Utama')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('npp')
                            ->label('NPP')
                            ->required()
                            ->maxLength(20),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        TextInput::make('password')
                            ->password()
                            ->dehydrateStateUsing(fn($state) => filled($state) ? Hash::make($state) : null)
                            ->dehydrated(fn($state) => filled($state))
                            ->required(fn(string $operation): bool => $operation === 'create'),
                        Select::make('roles')
                            ->relationship('roles', 'name')
                            ->required()
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                // Clear manager selection when roles change
                                $set('manager_id', null);
                            }),
                        TextInput::make('position')
                            ->label('Jabatan')
                            ->required(),
                        Select::make('divisions')
                            ->label('Divisi')
                            ->required()
                            ->relationship('divisions', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (is_array($state) && count($state) > 0) {
                                    $set('division_id', $state[0]);
                                }
                            }),
                        Select::make('division_id')
                            ->label('Divisi Utama')
                            ->relationship('division', 'name')
                            ->searchable()
                            ->preload()
                            ->reactive(),
                        Select::make('manager_id')
                            ->label('Manager/Atasan')
                            ->searchable()
                            ->preload()
                            ->options(function () {
                                // Show all users with any manager role from any division
                                return User::whereHas('roles', function ($query) {
                                    $query->where('name', 'like', 'manager_%')
                                          ->orWhere('name', 'like', 'kepala_%')
                                          ->orWhere('name', 'like', 'head_%');
                                })
                                ->pluck('name', 'id')
                                ->toArray();
                            })
                            ->placeholder('Pilih Manager/Atasan')
                            ->helperText('Pilih manager dari semua divisi yang tersedia'),
                    ])->columns(2),

                Section::make('Informasi Personal')
                    ->schema([
                        Select::make('gender')
                            ->options([
                                'Laki-laki' => 'Laki-laki',
                                'Perempuan' => 'Perempuan',
                            ]),
                        TextInput::make('ktp')
                            ->label('Nomor KTP')
                            ->maxLength(16),
                        DatePicker::make('birth')
                            ->label('Tanggal Lahir'),
                        TextInput::make('no_phone')
                            ->label('No. Telepon')
                            ->tel()
                            ->maxLength(20),
                        Select::make('last_education')
                            ->label('Pendidikan Terakhir')
                            ->options([
                                'sd' => 'SD',
                                'smp' => 'SMP',
                                'sma' => 'SMA',
                                'diploma' => 'Diploma',
                                's1' => 'S1',
                                's2' => 'S2',
                                's3' => 'S3',
                            ]),
                        Forms\Components\Textarea::make('address')
                            ->label('Alamat')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        $user = Auth::user();
        $isHrd = $user && $user->hasRole('hrd');
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                // Hanya tampilkan kolom berikut jika bukan HRD
                ...(!$isHrd ? [
                    TextColumn::make('email')
                        ->searchable(),
                    TextColumn::make('roles.name')
                        ->label('Roles')
                        ->sortable(),
                    TextColumn::make('division.name')
                        ->label('Division')
                        ->searchable(),
                    TextColumn::make('position')
                        ->label('Jabatan')
                        ->searchable(),
                    TextColumn::make('manager.name')
                        ->label('Manager/Atasan')
                        ->searchable()
                        ->placeholder('Tidak ada'),
                    TextColumn::make('created_at')
                        ->dateTime()
                        ->sortable(),
                ] : []),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->preload()
                    ->searchable(),
            ])
            ->actions([
            
                ...($isHrd ? [
                    Tables\Actions\Action::make('download_cuti')
                        ->label('Download Cuti')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('success')
                        ->action(function ($record) {
                            $year = now()->year;
                            $filename = 'laporan_cuti_' . $record->name . '_' . $year . '.xlsx';
                            return (new \App\Exports\LeaveYearlyExport($year, $record->id))->download($filename);
                        }),

                    Tables\Actions\Action::make('download_lembur_pdf')
                        ->label('Download Lembur PDF')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('warning')
                        ->visible(fn() => Auth::user() && Auth::user()->hasRole('hrd'))
                        ->form([
                            \Filament\Forms\Components\Select::make('month')
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
                                ->default(now()->month)
                                ->required(),
                            \Filament\Forms\Components\TextInput::make('year')
                                ->label('Tahun')
                                ->numeric()
                                ->default(now()->year)
                                ->minValue(2020)
                                ->maxValue(2030)
                                ->required(),
                        ])
                        ->action(function ($record, array $data) {
                            $url = route('overtime.user.monthly.pdf', [
                                'user_id' => $record->id,
                                'month' => $data['month'],
                                'year' => $data['year'],
                            ]);
                            return redirect($url);
                        }),
                ] : []),
                Tables\Actions\Action::make('export_laporan_harian')
                    ->label('Download Laporan Harian')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('red')
                    ->visible(function () {
                        $user = Auth::user();
                        if ($user && method_exists($user, 'getRoleNames')) {
                            return in_array('hrd', $user->getRoleNames()->toArray());
                        } elseif ($user && property_exists($user, 'roles')) {
                            return collect($user->roles)->pluck('name')->contains('hrd');
                        }
                        return false;
                    })
                    ->modalHeading(fn($record) => 'Export Laporan - ' . $record->name)
                    ->modalDescription('Export laporan harian karyawan ini')
                    ->modalWidth('md')
                    ->form([
                        TextInput::make('user_info')
                            ->label('Karyawan')
                            ->disabled()
                            ->default(fn($record) => $record->name . ' (' . ($record->npp ?? 'No NPP') . ')'),
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
                    ->action(function ($record, array $data) {
                        $year = $data['year'];
                        $userName = str_replace(' ', '_', $record->name);
                        $filename = "laporan_harian_{$userName}_{$year}.xlsx";
                        return (new DailyReportExcel($year, $record->id))->download($filename);
                    }),
                    
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
            'view' => Pages\ViewUser::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        $isSuperAdmin = false;
        $isHrd = false;
        if ($user) {
            if (method_exists($user, 'getRoleNames')) {
                $roles = $user->getRoleNames()->toArray();
                $isSuperAdmin = in_array('super_admin', $roles);
                $isHrd = in_array('hrd', $roles);
            } elseif (property_exists($user, 'roles')) {
                $roleNames = collect($user->roles)->pluck('name');
                $isSuperAdmin = $roleNames->contains('super_admin');
                $isHrd = $roleNames->contains('hrd');
            }
        }
        
        if ($isSuperAdmin) {
            // Super admin can see all users
            return parent::getEloquentQuery();
        } elseif ($isHrd) {
            // HRD can see all users except super admin
            return parent::getEloquentQuery()
                ->whereDoesntHave('roles', function ($query) {
                    $query->where('name', 'super_admin');
                });
        } else {
            // Other users can only see users they created
            return parent::getEloquentQuery()
                ->where('created_by', $user ? $user->id : null);
        }
    }
}