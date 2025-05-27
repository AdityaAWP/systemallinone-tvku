<?php

namespace App\Filament\Resources\JournalResource\Pages;

use App\Filament\Resources\JournalResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateJournal extends CreateRecord
{
    protected static string $resource = JournalResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return [...$data, 'intern_id' => Auth::id()];
    }
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
