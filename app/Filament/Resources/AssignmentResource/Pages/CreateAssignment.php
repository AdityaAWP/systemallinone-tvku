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
        // For paid assignments, set default approval status to pending
        if ($data['type'] === Assignment::TYPE_PAID) {
            $data['approval_status'] = Assignment::STATUS_PENDING;
        } else {
            // For free and barter assignments, auto-approve
            $data['approval_status'] = Assignment::STATUS_APPROVED;
            $data['approved_by'] = Auth::id();
            $data['approved_at'] = now();
        }

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return static::getModel()::create($data);
    }
}