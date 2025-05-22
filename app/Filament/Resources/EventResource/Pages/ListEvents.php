<?php

namespace App\Filament\Resources\EventResource\Pages;

use App\Filament\Resources\EventResource;
// use Filament\Actions; // Removed unused import
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use App\Models\Event;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Grid;

class ListEvents extends ListRecords
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('Tambah Event')
                ->icon('heroicon-o-plus')
                ->modalHeading('Tambah Event Baru')
                ->modalWidth('4xl')
                ->modalSubmitActionLabel('Simpan')
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
                                ->required()
                                ->withoutSeconds()
                                ->displayFormat('d M Y H:i'),

                            DateTimePicker::make('ends_at')
                                ->label('Berakhir Pada')
                                ->required()
                                ->withoutSeconds()
                                ->displayFormat('d M Y H:i'),
                        ])
                        ->columnSpan('full'),

                    Textarea::make('description')
                        ->label('Deskripsi')
                        ->rows(5)
                        ->maxLength(500)
                        ->required()
                        ->columnSpan('full'),
                ])
                ->action(function (array $data) {
                    Event::create($data);
                    \Filament\Notifications\Notification::make()
                        ->title('Event berhasil ditambahkan')
                        ->success()
                        ->send();
                }),
        ];
    }
}
