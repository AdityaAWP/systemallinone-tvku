<?php

namespace App\Filament\Resources\OutgoingLetterResource\Pages;

use App\Filament\Resources\OutgoingLetterResource;
use App\Models\OutgoingLetter;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateOutgoingLetter extends CreateRecord
{
    protected static string $resource = OutgoingLetterResource::class;
    
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Pastikan reference_number di-generate otomatis jika kosong
        if (empty($data['reference_number']) && !empty($data['type'])) {
            $data['reference_number'] = OutgoingLetter::generateNextReferenceNumber($data['type']);
        }
        
        return $data;
    }
}
