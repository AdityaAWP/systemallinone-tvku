<?php

namespace App\Filament\Resources;

use App\Filament\Exports\DailyReportExporter;
use App\Filament\Resources\DailyReportResource\Pages;
use App\Models\DailyReport;
use Filament\Actions\Exports\Models\Export;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Carbon;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
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
use Illuminate\Support\HtmlString;

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
                Forms\Components\Section::make('Informasi Waktu Kerja')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                DatePicker::make('entry_date')
                                    ->label('Tanggal Kerja')
                                    ->required(),
                                TimePicker::make('check_in')
                                    ->label('Waktu Mulai')
                                    ->seconds(false)
                                    ->required(),
                                TimePicker::make('check_out')
                                    ->label('Waktu Selesai')
                                    ->seconds(false)
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        $this->calculateWorkHours($get, $set);
                                    }),
                            ]),
                        Forms\Components\Grid::make(4)
                            ->schema([
                                TextInput::make('work_hours_component')
                                    ->label('Total Jam Kerja')
                                    ->disabled()
                                    ->numeric()
                                    ->columnSpan(1),
                                TextInput::make('work_minutes_component')
                                    ->label('Total Menit Kerja')
                                    ->disabled()
                                    ->numeric()
                                    ->columnSpan(1),
                            ]),
                    ]),
                Forms\Components\Section::make('Deskripsi Pekerjaan')
                    ->schema([
                        RichEditor::make('description')
                            ->label('')
                            ->required()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    protected static function calculateWorkHours(Get $get, Set $set): void
    {
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
            } catch (\Exception $e) {
                // Handle error if needed
            }
        }
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                ExportAction::make()
                    ->exporter(DailyReportExporter::class)
                    ->label('Ekspor Excel')
                    ->icon('heroicon-o-document-arrow-down'),
            ])
            ->columns([
                TextColumn::make('entry_date')
                    ->label('Tanggal')
                    ->date('d F Y')
                    ->sortable(),
                TextColumn::make('check_in')
                    ->label('Jam Masuk')
                    ->dateTime('H:i'),
                TextColumn::make('check_out')
                    ->label('Jam Keluar')
                    ->dateTime('H:i'),
                TextColumn::make('hours_formatted')
                    ->label('Durasi Kerja')
                    ->state(fn (DailyReport $record): string => "{$record->work_hours_component} jam {$record->work_minutes_component} menit"),
                TextColumn::make('description')
                    ->label('Deskripsi Pekerjaan')
                    ->html()
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        return strip_tags($state);
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('bulan')
                    ->label('Bulan')
                    ->options(function () {
                        $months = [];
                        $reports = DailyReport::query()
                            ->where('user_id', auth()->id())
                            ->selectRaw('DISTINCT DATE_FORMAT(entry_date, "%Y-%m") as month')
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
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    ExportBulkAction::make()
                        ->exporter(DailyReportExporter::class)
                        ->label('Ekspor Data Terpilih')
                        ->icon('heroicon-o-document-arrow-down'),
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
        ];
    }
}