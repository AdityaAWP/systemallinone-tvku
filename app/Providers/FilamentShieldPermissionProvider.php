<?php

namespace App\Providers;

use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Illuminate\Support\Collection;

class FilamentShieldPermissionProvider implements HasShieldPermissions
{
    public function getPermissions(): Collection
    {
        return collect([
            // Custom permissions for AssignmentResource
            'view_assignments' => [
                'label' => 'View Assignments',
                'description' => 'View all assignments',
            ],
            'create_assignments' => [
                'label' => 'Create Assignments',
                'description' => 'Create new assignments',
            ],
            'update_assignments' => [
                'label' => 'Update Assignments',
                'description' => 'Update existing assignments',
            ],
            'delete_assignments' => [
                'label' => 'Delete Assignments',
                'description' => 'Delete assignments',
            ],
            'approve_assignments' => [
                'label' => 'Approve Assignments',
                'description' => 'Approve or decline assignments',
            ],
        ]);
    }

    public static function getPermissionPrefixes(): array
    {
        return [
            'assignments',
        ];
    }
}