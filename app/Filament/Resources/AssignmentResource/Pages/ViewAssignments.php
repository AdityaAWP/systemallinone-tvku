<?php

namespace App\Filament\Resources\AssignmentResource\Pages;

use App\Filament\Resources\AssignmentResource;
use App\Models\Assignment;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewAssignments extends ViewRecord
{
    protected static string $resource = AssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make()
                ->hidden(fn (Assignment $record) => 
                    $record->approval_status !== Assignment::STATUS_PENDING || 
                    Auth::user()->hasRole('direktur_keuangan') || 
                    (Auth::user()->hasRole('staff_keuangan') && $record->created_by !== Auth::id())),
        ];
    }
    
    // Prevent staff_keuangan from viewing assignments they didn't create
    protected function authorizeAccess(): void
    {
        parent::authorizeAccess();
        
        $record = $this->getRecord();
        
        if (Auth::user()->hasRole('staff_keuangan') && $record->created_by !== Auth::id()) {
            abort(403, 'You are not authorized to view this assignment.');
        }
    }
}