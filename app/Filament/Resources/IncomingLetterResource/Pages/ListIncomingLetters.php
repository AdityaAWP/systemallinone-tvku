<?php

namespace App\Filament\Resources\IncomingLetterResource\Pages;

use App\Filament\Resources\IncomingLetterResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;

class ListIncomingLetters extends ListRecords
{
    protected static string $resource = IncomingLetterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Data')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),
            'internal' => Tab::make('Internal')
                ->query(fn($query) => $query->where('type', 'internal')),
            'general' => Tab::make('Umum')
                ->query(fn($query) => $query->where('type', 'general')),
            'visit' => Tab::make('Kunjungan/Prakerin')
                ->query(fn($query) => $query->where('type', 'visit')),
        ];
    }
}
