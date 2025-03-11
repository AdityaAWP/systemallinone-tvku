<?php

namespace App\Filament\Resources\LogisticsResource\Pages;

use App\Filament\Resources\LogisticsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Arr;

class ListLogistics extends ListRecords
{
    protected static string $resource = LogisticsResource::class;

    protected static ?string $title = "Logistics";
    protected static ?string $navigationBadgeTooltip = "Logistics";


    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
