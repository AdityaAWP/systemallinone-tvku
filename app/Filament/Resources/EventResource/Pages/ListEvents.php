<?php

namespace App\Filament\Resources\EventResource\Pages;

use App\Filament\Resources\EventResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use App\Models\Event;

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
                ->modalWidth('xl')
                ->modalSubmitActionLabel('Simpan')
                ->form([
                    TextInput::make('name')
                        ->label('Name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('description')
                        ->label('Deskripsi')
                        ->nullable()
                        ->maxLength(255),
                    DateTimePicker::make('starts_at')
                        ->label('Mulai Pada')
                        ->required(),
                    DateTimePicker::make('ends_at')
                        ->label('Berakhir Pada')
                        ->required(),
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
