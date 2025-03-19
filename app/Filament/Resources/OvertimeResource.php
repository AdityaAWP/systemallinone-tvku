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
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Blade;

class OvertimeResource extends Resource
{
    protected static ?string $model = Overtime::class;
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationGroup = 'Main Menu';
    protected static ?int $navigationSort = 1;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(3)
                    ->schema([
                        DatePicker::make('tanggal_overtime')
                            ->label('Tanggal Lembur')
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
                            ->columnSpan(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tanggal_overtime')
                    ->searchable()
                    ->date('d F Y')
                    ->sortable(),
                TextColumn::make('check_in')
                    ->searchable()
                    ->dateTime('H:i'),
                TextColumn::make('check_out')
                    ->searchable()
                    ->dateTime('H:i'),
                TextColumn::make('overtime_formatted')
                    ->searchable()
                    ->label('Durasi Overtime')
                    ->state(fn(Overtime $record): string => "{$record->overtime_hours} jam {$record->overtime_minutes} menit"),
                TextColumn::make('description')
                    ->searchable()
                    ->label('Description'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('download') 
                    ->url(fn(Overtime $overtime) => route('overtime.single', $overtime))
                    ->openUrlInNewTab()
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
