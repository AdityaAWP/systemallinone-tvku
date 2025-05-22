<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EventResource\Pages;
use App\Filament\Resources\EventResource\RelationManagers;
use App\Models\Event;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Carbon;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Resources\Resource;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationGroup = 'Main Menu';
    protected static ?string $navigationLabel = 'Kalender';
    protected static ?string $label = 'Kalender';
    protected static ?int $navigationSort = -2;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('description')
                    ->label('Deskripsi')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('starts_at')
                    ->label('Mulai Pada')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->label('Berakhir Pada')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Lihat')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Detail Event')
                    ->modalWidth('4xl')
                    ->url(null)
                    ->openUrlInNewTab(false)
                    ->form(fn($record) => [
                        TextInput::make('name')
                            ->label('Name')
                            ->disabled()
                            ->default($record->name)
                            ->columnSpan('full'),

                        Grid::make(2)
                            ->schema([
                                DateTimePicker::make('starts_at')
                                    ->label('Mulai Pada')
                                    ->disabled()
                                    ->default(Carbon::parse($record['starts_at'])->format('Y-m-d H:i:s')),

                                DateTimePicker::make('ends_at')
                                    ->label('Berakhir Pada')
                                    ->disabled()
                                    ->default(Carbon::parse($record['ends_at'])->format('Y-m-d H:i:s')),
                            ])
                            ->columnSpan('full'),

                        Textarea::make('description')
                            ->label('Deskripsi')
                            ->disabled()
                            ->rows(5)
                            ->default($record->description ?? '-')
                            ->columnSpan('full'),
                    ])
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),

                Tables\Actions\Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil-square')
                    ->modalHeading('Edit Event')
                    ->modalWidth('4xl')
                    ->form([

                        TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan('full'),
                        Grid::make(2)
                            ->schema([
                                DateTimePicker::make('starts_at')
                                    ->label('Mulai Pada')
                                    ->required(),
                                DateTimePicker::make('ends_at')
                                    ->label('Berakhir Pada')
                                    ->required(),
                            ])
                            ->columnSpan('full'),
                        Textarea::make('description')
                            ->label('Deskripsi')
                            ->nullable()
                            ->rows(5)
                            ->maxLength(255)
                            ->columnSpan('full'),
                    ])
                    ->fillForm(function ($record) {
                        return [
                            'name' => $record->name,
                            'description' => $record->description,
                            'starts_at' => $record->starts_at,
                            'ends_at' => $record->ends_at,
                        ];
                    })
                    ->action(function (array $data, $record) {
                        $record->update($data);

                        \Filament\Notifications\Notification::make()
                            ->title('Event berhasil diupdate')
                            ->success()
                            ->send();
                    }),

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
            'index' => Pages\ListEvents::route('/'),
            'view' => Pages\ViewEvent::route('/{record}'),
            'edit' => Pages\EditEvent::route('/{record}/edit'),
        ];
    }
}
