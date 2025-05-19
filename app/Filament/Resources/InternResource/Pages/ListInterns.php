<?php

namespace App\Filament\Resources\InternResource\Pages;

use App\Filament\Resources\InternResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ListRecords\Tab; // Perbaiki namespace ini

class ListInterns extends ListRecords
{
    protected static string $resource = InternResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),
            'intern' => Tab::make('Magang Perguruan Tinggi')
                ->query(fn ($query) => $query->whereHas('school', function ($q) {
                    $q->where('type', 'Perguruan Tinggi');
                })),
            'intern_school' => Tab::make('Magang SMA/SMK')
                ->query(fn ($query) => $query->whereHas('school', function ($q) {
                    $q->where('type', 'SMA/SMK');
                })),
        ];
    }
}