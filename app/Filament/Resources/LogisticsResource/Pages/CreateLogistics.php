<?php

namespace App\Filament\Resources\LogisticsResource\Pages;

use App\Filament\Resources\LogisticsResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateLogistics extends CreateRecord
{
    protected static string $resource = LogisticsResource::class;
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
