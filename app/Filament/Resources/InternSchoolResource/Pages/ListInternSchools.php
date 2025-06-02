<?php

namespace App\Filament\Resources\InternSchoolResource\Pages;

use App\Filament\Resources\InternSchoolResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ListRecords\Tab;

class ListInternSchools extends ListRecords
{
    protected static string $resource = InternSchoolResource::class;

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
            'smk' => Tab::make('SMK')
                ->query(fn ($query) => $query->where('type', 'SMA/SMK')),
            'perguruan_tinggi' => Tab::make('Perguruan Tinggi')
                ->query(fn ($query) => $query->where('type', 'Perguruan Tinggi')),
        ];
    }
}
