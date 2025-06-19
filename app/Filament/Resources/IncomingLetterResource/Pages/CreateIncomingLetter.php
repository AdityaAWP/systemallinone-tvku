<?php

namespace App\Filament\Resources\IncomingLetterResource\Pages;

use App\Filament\Resources\IncomingLetterResource;
use App\Models\IncomingLetter;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateIncomingLetter extends CreateRecord
{
    protected static string $resource = IncomingLetterResource::class;
    
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generate reference number if not provided
        if (empty($data['reference_number']) && !empty($data['type'])) {
            $data['reference_number'] = IncomingLetter::generateNextReferenceNumber($data['type']);
        }
        
        return $data;
    }
}
