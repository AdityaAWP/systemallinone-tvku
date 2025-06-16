<?php

namespace App\Filament\Resources\AssignmentResource\Pages;

use App\Filament\Resources\AssignmentResource;
use App\Models\Assignment;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateAssignment extends CreateRecord
{
    protected static string $resource = AssignmentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set default statuses for new assignments
        $data['approval_status'] = Assignment::STATUS_PENDING;
        $data['submit_status'] = Assignment::SUBMIT_BELUM;
        
        // MODIFIED: Ensure a default priority is always set on creation,
        // since the field is disabled for non-directors.
        $data['priority'] = $data['priority'] ?? Assignment::PRIORITY_NORMAL;
        
        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return static::getModel()::create($data);
    }
}