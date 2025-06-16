<?php

namespace App\Filament\Widgets;

use App\Models\Event;
use Filament\Forms\Form;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\RichEditor;
use Illuminate\Database\Eloquent\Model;
use Saade\FilamentFullCalendar\Data\EventData;
use Saade\FilamentFullCalendar\Actions\EditAction;
use Saade\FilamentFullCalendar\Actions\DeleteAction;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class CalendarWidget extends FullCalendarWidget
{
    public Model | string | null $model = Event::class;

    public function fetchEvents(array $fetchInfo): array
    {
        return Event::query()
            ->where('starts_at', '>=', $fetchInfo['start'])
            ->where('ends_at', '<=', $fetchInfo['end'])
            ->get()
            ->map(
                fn(Event $event) => EventData::make()
                    ->id($event->id)
                    ->title($event->name)
                    ->start($event->starts_at)
                    ->end($event->ends_at)
            )
            ->toArray();
    }

    public function getFormSchema(): array
    {
        return [
            TextInput::make('name')
                ->label('Nama Event')
                ->required()
                ->maxLength(255)
                ->columnSpan('full'),
            Grid::make(2)
                ->schema([
                    DateTimePicker::make('starts_at')
                        ->label('Mulai')
                        ->required(),
                    DateTimePicker::make('ends_at')
                        ->label('Berakhir')
                        ->required(),
                ])
                ->columnSpan('full'),
            RichEditor::make('description')
                ->label('Deskripsi')
                ->required()
                ->columnSpan(2),
        ];
    }

    protected function modalActions(): array
    {
        return [
            EditAction::make()
                ->label('Update')
                ->mountUsing(function (Event $record, Form $form, array $arguments) {
                    $form->fill([
                        'name' => $record->name,
                        'description' => $record->description,
                        'starts_at' => $arguments['event']['start'] ?? $record->starts_at,
                        'ends_at' => $arguments['event']['end'] ?? $record->ends_at,
                    ]);
                }),

            DeleteAction::make()
                ->label('Hapus')
                ->requiresConfirmation()
                ->successNotificationTitle('Event berhasil dihapus'),
        ];
    }

    public static function getSort(): int
    {
        return 5;
    }
}
