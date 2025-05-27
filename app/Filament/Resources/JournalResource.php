<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JournalResource\Pages;
use App\Filament\Resources\JournalResource\RelationManagers;
use App\Models\Journal;
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
    protected static ?string $navigationGroup = 'Menu Magang';
    protected static ?string $navigationLabel = 'Jurnal';
    protected static ?string $title = 'Jurnal';
    protected static ?string $label = 'Jurnal';
    protected static ?int $navigationSort = 0;
    public static function getPanel(): ?string
    {
        return 'intern';
    }

    public static function canViewAny(): bool
    {
        return Auth::guard('intern')->check();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Hidden::make('user_id')
                    ->default(fn() => Auth::id()),
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
                    ->nullable(),
                Textarea::make('reason_of_absence')
                    ->visible(fn(Forms\Get $get): bool => in_array($get('status'), ['Izin', 'Sakit']))
                    ->required(fn(Forms\Get $get): bool => in_array($get('status'), ['Izin', 'Sakit']))
                    ->placeholder('Please provide reason for absence'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('entry_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('start_time')
                    ->time('H:i')
                    ->sortable(),
                TextColumn::make('end_time')
                    ->time('H:i')
                    ->sortable(),
                TextColumn::make('activity')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Hadir' => 'success',
                        'Izin' => 'warning',
                        'Sakit' => 'danger',
                    }),
                ImageColumn::make('image')
                    ->circular(),
            ])
            ->filters([
                //
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
