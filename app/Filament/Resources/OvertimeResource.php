<?php
namespace App\Filament\Resources;

use App\Filament\Resources\OvertimeResource\Pages;
use App\Models\Overtime;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Carbon;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use HusamTariq\FilamentTimePicker\Forms\Components\TimePickerField;

class OvertimeResource extends Resource
{
    protected static ?string $model = Overtime::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    public static function form(Form $form): Form
    
    {
        return $form
            ->schema([
                DatePicker::make('tanggal_overtime')
                    ->label('Tanggal Lembur')
                    ->required(),
                TimePickerField::make('check_in')->label('Waktu Check-in'),
                TimePicker::make('check_out')
                ->label('Waktu Check-out')
                ->required()
                ->afterStateUpdated(function (Get $get, Set $set) {
                    $checkIn = $get('check_in');
                    $checkOut = $get('check_out');

                    if ($checkIn && $checkOut) {
                        $checkInTime = Carbon::parse($checkIn);
                        $checkOutTime = Carbon::parse($checkOut);

                        $totalHours = $checkOutTime->diffInMinutes($checkInTime) / 60;
                        $overtimeHours = max(0, $totalHours - 8); 

                        $set('overtime', $overtimeHours);
                    }
                }),

                TextInput::make('overtime')
                    ->label('Overtime (hours)')
                    ->disabled()
                    ->numeric(),
                TextInput::make('description')
                    ->label('Deskripsi')
                    ->required()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tanggal_overtime')->sortable(),
                TextColumn::make('check_in'),
                TextColumn::make('check_out'),
                TextColumn::make('overtime')->label('Overtime (hours)'),
                TextColumn::make('description')->label('Deskripsi'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
