<?php

namespace App\Filament\Resources;

use App\Filament\Exports\DailyReportExporter;
use App\Filament\Resources\DailyReportResource\Pages;
use App\Models\DailyReport;
use Filament\Actions\Exports\Models\Export;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ExportBulkAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\ExportAction;
use Illuminate\Database\Eloquent\Builder;

class DailyReportResource extends Resource
{
    protected static ?string $model = DailyReport::class;
    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';
    protected static ?string $navigationGroup = 'Menu Karyawan';
    protected static ?string $label = 'Laporan Harian';
    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
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
                            ->label('Waktu Check-in')
                            ->seconds(false)
                            ->required(),
                        TimePicker::make('check_out')
                            ->label('Waktu Check-out')
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
                        TextInput::make('description')
                            ->label('Deskripsi')
                            ->required()
                            ->columnSpan(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                ExportAction::make()->exporter(DailyReportExporter::class),
            ])
            ->columns([
                TextColumn::make('entry_date')
                    ->label('Tanggal')
                    ->searchable()
                    ->date('d F Y')
                    ->sortable(),
                TextColumn::make('check_in')
                ->label('Waktu Check-in')
                    ->searchable()
                    ->dateTime('H:i'),
                TextColumn::make('check_out')
                    ->label('Waktu Check-out')
                    ->searchable()
                    ->dateTime('H:i'),
                TextColumn::make('hours_formatted')
                    ->searchable()
                    ->label('Durasi Kerja')
                    ->state(fn(DailyReport $record): string => "{$record->work_hours_component} jam {$record->work_minutes_component} menit"),
                TextColumn::make('description')
                    ->searchable()
                    ->label('Deskripsi'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    ExportBulkAction::make()
                        ->exporter(DailyReportExporter::class)
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
        ];
    }
}
