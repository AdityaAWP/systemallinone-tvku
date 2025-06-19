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
        
        // MODIFIED: Set default priority berdasarkan role user
        $user = Auth::user();
        if ($user && method_exists($user, 'hasRole')) {
            // Untuk staff_keuangan, biarkan priority kosong (null)
            if ($user->hasRole('staff_keuangan')) {
                // Jangan set default priority untuk staff_keuangan
                if (!isset($data['priority'])) {
                    $data['priority'] = null;
                }
            } else {
                // Untuk role lain, set default priority normal jika tidak ada nilai
                $data['priority'] = $data['priority'] ?? Assignment::PRIORITY_NORMAL;
            }
        } else {
            // Fallback jika tidak ada role system
            $data['priority'] = $data['priority'] ?? Assignment::PRIORITY_NORMAL;
        }
        
        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return static::getModel()::create($data);
    }
}