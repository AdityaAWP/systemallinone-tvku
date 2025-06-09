<?php

namespace App\Filament\Resources;

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
    
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', Auth::user()?->id);
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
                                0 => 'Hari Kerja Normal',
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
                            ->required(fn (Get $get): bool => $get('is_holiday') == 0)
                            ->hidden(fn (Get $get): bool => $get('is_holiday') == 1)
                            ->seconds(false),
                        TimePicker::make('normal_work_time_check_out')
                            ->label('Waktu Selesai Kerja Normal')
                            ->required(fn (Get $get): bool => $get('is_holiday') == 0)
                            ->hidden(fn (Get $get): bool => $get('is_holiday') == 1)
                            ->seconds(false),
                    ])
                    ->hidden(fn (Get $get): bool => $get('is_holiday') == 1),

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
            ->columns([
                TextColumn::make('tanggal_overtime')
                    ->label('Tanggal Lembur')
                    ->searchable()
                    ->date('d F Y')
                    ->sortable(),
                TextColumn::make('is_holiday')
                    ->label('Status Hari')
                    ->formatStateUsing(fn (string $state): string => $state ? 'Hari Libur' : 'Hari Kerja')
                    ->badge()
                    ->color(fn (string $state): string => $state ? 'success' : 'primary'),
                TextColumn::make('normal_work_time_check_in')
                    ->label('Waktu Mulai Kerja')
                    ->searchable()
                    ->dateTime('H:i')
                    ->placeholder('Hari Libur')
                    ->hidden(fn ($record) => $record?->is_holiday),
                TextColumn::make('normal_work_time_check_out')
                    ->label('Waktu Selesai Kerja')
                    ->searchable()
                    ->dateTime('H:i')
                    ->placeholder('Hari Libur')
                    ->hidden(fn ($record) => $record?->is_holiday),
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
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_holiday')
                    ->label('Status Hari')
                    ->options([
                        0 => 'Hari Kerja Normal',
                        1 => 'Hari Libur'
                    ])
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('download')
                    ->url(fn(Overtime $overtime) => route('overtime.single', $overtime))
                    ->openUrlInNewTab()
            ])
            ->headerActions([
                // Action untuk download semua data
                Tables\Actions\Action::make('downloadAll')
                    ->label('Download Semua')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->url(fn() => route('overtime.report'))
                    ->openUrlInNewTab(),
                
                // Action untuk download berdasarkan bulan
                Tables\Actions\Action::make('downloadMonthly')
                    ->label('Download Bulanan')
                    ->icon('heroicon-o-calendar')
                    ->color('primary')
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
                            ])
                    ])
                    ->action(function (array $data) {
                        // Buat URL dengan parameter
                        $url = route('overtime.monthly') . '?' . http_build_query([
                            'month' => $data['month'],
                            'year' => $data['year']
                        ]);
                        
                        // Redirect ke URL dengan membuka tab baru
                        return redirect()->away($url);
                    }),
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
            'index' => Pages\ListOvertimes::route('/'),
            'create' => Pages\CreateOvertime::route('/create'),
            'edit' => Pages\EditOvertime::route('/{record}/edit'),
        ];
    }
}