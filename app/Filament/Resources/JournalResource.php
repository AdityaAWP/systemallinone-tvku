<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JournalResource\Pages;
use App\Filament\Resources\JournalResource\RelationManagers;
use App\Models\Journal;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class JournalResource extends Resource
{
    protected static ?string $model = Journal::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): ?string
    {
        if (Auth::guard('intern')->check()) {
            return 'Main Menu';
        }
        return 'Manajemen Magang';
    }

    public static function getNavigationLabel(): string
    {
        if (Auth::guard('intern')->check()) {
            return 'Jurnal';
        }
        return 'Jurnal Magang';
    }

    public static function getModelLabel(): string
    {
        if (Auth::guard('intern')->check()) {
            return 'Jurnal';
        }
        return 'Jurnal Magang';
    }

    public static function canViewAny(): bool
    {
        // Allow interns to see their own journals
        if (Auth::guard('intern')->check()) {
            return true;
        }
        
        // Only allow admin_magang from web guard to access journal management
        if (Auth::guard('web')->check()) {
            $user = Auth::guard('web')->user();
            return $user && $user->hasRole(['admin_magang', 'super_admin']);
        }
        
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('intern_id')
                    ->label('Nama Magang')
                    ->relationship('intern', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->visible(fn() => Auth::guard('web')->check())
                    ->default(null),
                Hidden::make('intern_id')
                    ->default(fn() => Auth::guard('intern')->check() ? Auth::guard('intern')->id() : null)
                    ->visible(fn() => Auth::guard('intern')->check()),
                DatePicker::make('entry_date')
                    ->label('Tanggal')
                    ->required()
                    ->default(now()),
                Select::make('status')
                    ->options([
                        'Hadir' => 'Hadir',
                        'Izin' => 'Izin',
                        'Sakit' => 'Sakit',
                    ])
                    ->default('Hadir')
                    ->live()
                    ->required(),
                TimePicker::make('start_time')
                    ->label('Waktu Mulai')
                    ->seconds(false)
                    ->required(fn(Forms\Get $get): bool => in_array($get('status'), ['Hadir'])),
                TimePicker::make('end_time')
                    ->label('Waktu Selesai')
                    ->required(fn(Forms\Get $get): bool => in_array($get('status'), ['Hadir']))
                    ->seconds(false),
                Textarea::make('activity')
                    ->label('Aktivitas')
                    ->required(fn(Forms\Get $get): bool => in_array($get('status'), ['Hadir'])),
                FileUpload::make('image')
                    ->image()
                    ->directory('journal-images')
                    ->preserveFilenames()
                    ->nullable()
                    ->label('Bukti Gambar'),
                Textarea::make('reason_of_absence')
                    ->label('Alasan Ketidak hadiran')
                    ->visible(fn(Forms\Get $get): bool => in_array($get('status'), ['Izin', 'Sakit']))
                    ->required(fn(Forms\Get $get): bool => in_array($get('status'), ['Izin', 'Sakit']))
                    ->placeholder('Silakan isi alasan ketidakhadiran'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('intern.name')
                    ->label('Nama Magang')
                    ->searchable()
                    ->sortable()
                    ->visible(fn() => Auth::guard('web')->check()),
                TextColumn::make('entry_date')
                    ->date()
                    ->label('Tanggal')
                    ->sortable(),
                TextColumn::make('start_time')
                    ->time('H:i')
                    ->label('Waktu Mulai')
                    ->sortable(),
                TextColumn::make('end_time')
                    ->time('H:i')
                    ->label('Waktu Selesai')
                    ->sortable(),
                TextColumn::make('activity')
                    ->label('Aktivitas')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),
                TextColumn::make('reason_of_absence')
                    ->label('Alasan Ketidak hadiran')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Hadir' => 'success',
                        'Izin' => 'warning',
                        'Sakit' => 'danger',
                    }),
                ImageColumn::make('image')
                    ->label('Bukti Gambar')
                    ->circular(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'Hadir' => 'Hadir',
                        'Izin' => 'Izin',
                        'Sakit' => 'Sakit',
                    ]),
                Tables\Filters\SelectFilter::make('intern_id')
                    ->label('Nama Magang')
                    ->relationship('intern', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn() => Auth::guard('web')->check()),
                Tables\Filters\Filter::make('entry_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('entry_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('entry_date', '<=', $date),
                            );
                    }),
            ])
            ->headerActions([
                // Action untuk download semua data
                Tables\Actions\Action::make('downloadAll')
                    ->label('Download Semua')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->url(fn() => route('journal.report'))
                    ->openUrlInNewTab(),
                
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
                        $url = route('journal.monthly') . '?' . http_build_query([
                            'month' => $data['month'],
                            'year' => $data['year']
                        ]);
                        
                        return redirect()->away($url);
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(function (Builder $query) {
                if (Auth::guard('intern')->check()) {
                    return $query->where('intern_id', Auth::guard('intern')->id());
                }
                return $query;
            });
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
            'index' => Pages\ListJournals::route('/'),
            'create' => Pages\CreateJournal::route('/create'),
            'edit' => Pages\EditJournal::route('/{record}/edit'),
        ];
    }
}
