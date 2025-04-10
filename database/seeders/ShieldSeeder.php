<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class ShieldSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create roles
        $hrdRole = Role::create(['name' => 'hrd']);
        $managerRole = Role::create(['name' => 'manager']);
        $staffRole = Role::create(['name' => 'staff']);

        // Create permissions for Leave management
        $leavePermissions = [
            'view_any_leave' => ['HRD', 'Manager', 'Staff'],
            'view_leave' => ['HRD', 'Manager', 'Staff'],
            'create_leave' => ['Staff'],
            'update_leave' => ['HRD', 'Staff'],
            'delete_leave' => ['HRD'],
            'approve_leave' => ['HRD', 'Manager'],
            'reject_leave' => ['HRD', 'Manager'],
            'generate_leave_report' => ['HRD'],
        ];

        foreach ($leavePermissions as $permission => $roles) {
            $permissionModel = Permission::firstOrCreate(['name' => $permission]);
            
            foreach ($roles as $roleName) {
                Role::findByName($roleName)->givePermissionTo($permissionModel);
            }
        }

        // // Create test users
        // $this->createUser('HRD User', 'hrd@example.com', 'password', $hrdRole);
        // $this->createUser('Manager User', 'manager@example.com', 'password', $managerRole);
        // $this->createUser('Staff User', 'staff@example.com', 'password', $staffRole);
    }

    private function createUser($name, $email, $password, $role)
    {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt($password),
        ]);

        $user->assignRole($role);

        return $user;
    }
}