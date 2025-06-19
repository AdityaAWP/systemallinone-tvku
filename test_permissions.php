<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->boot();

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

$role = Role::where('name', 'super_admin')->first();
$permissions = Permission::where('name', 'like', '%intern%division%')->get();

echo "Super Admin Role: " . ($role ? "Found" : "Not Found") . "\n";
echo "InternDivision Permissions Count: " . $permissions->count() . "\n";

if ($role && $permissions->count() > 0) {
    $role->givePermissionTo($permissions);
    echo "Permissions assigned to super_admin\n";
    
    // Check if permissions are assigned
    $assignedPermissions = $role->permissions()->where('name', 'like', '%intern%division%')->get();
    echo "Assigned Permissions Count: " . $assignedPermissions->count() . "\n";
} else {
    echo "Failed to assign permissions\n";
}
