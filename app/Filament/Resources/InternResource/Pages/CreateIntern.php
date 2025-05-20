<?php

namespace App\Filament\Resources\InternResource\Pages;

use App\Filament\Resources\InternResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateIntern extends CreateRecord
{
    protected static string $resource = InternResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Kita tidak perlu menyimpan institution_type karena itu hanya untuk UI
        if (isset($data['institution_type'])) {
            unset($data['institution_type']);
        }
        
        return $data;
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}