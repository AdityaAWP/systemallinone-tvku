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

        // Create all roles
        $roles = [
            'hrd',
            'manager',
            'staff',
            'direktur_keuangan',  
            'admin_keuangan',   
            'staff_keuangan',
            'admin_umum'
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        // Create and assign leave permissions
        $this->createLeavePermissions();

        // Create and assign letter permissions
        $this->createLetterPermissions();

        // Create test users (uncomment if needed)
        $this->createTestUsers();
    }

    private function createLeavePermissions()
    {
        $leavePermissions = [
            'view_any_leave' => ['hrd', 'manager', 'staff'],
            'view_leave' => ['hrd', 'manager', 'staff'],
            'create_leave' => ['staff'],
            'update_leave' => ['hrd', 'staff'],
            'delete_leave' => ['hrd'],
            'approve_leave' => ['hrd', 'manager'],
            'reject_leave' => ['hrd', 'manager'],
            'generate_leave_report' => ['hrd'],
        ];

        foreach ($leavePermissions as $permission => $roles) {
            $permissionModel = Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
            
            foreach ($roles as $roleName) {
                $role = Role::where('name', $roleName)->first();
                if ($role) {
                    $role->givePermissionTo($permissionModel);
                }
            }
        }
    }

    private function createLetterPermissions()
    {
        // Define permissions with roles for assignment
        $assignmentPermissions = [
            'view_any_assignment' => ['direktur_keuangan', 'admin_keuangan', 'staff_keuangan'],
            'view_assignment' => ['direktur_keuangan', 'admin_keuangan', 'staff_keuangan'],
            'create_assignment' => ['direktur_keuangan', 'admin_keuangan', 'staff_keuangan'],
            'update_assignment' => ['direktur_keuangan', 'admin_keuangan', 'staff_keuangan'],
            'delete_assignment' => ['direktur_keuangan', 'admin_keuangan', 'staff_keuangan'],
            'delete_any_assignment' => ['direktur_keuangan', 'admin_keuangan'],
            'approve_assignment' => ['direktur_keuangan'],
            'reject_assignment' => ['direktur_keuangan'],
        ];

        // Define permissions with roles for incoming letters
        $incomingLetterPermissions = [
            'view_any_incomingletter' => ['direktur_keuangan', 'admin_umum'],
            'view_incomingletter' => ['direktur_keuangan', 'admin_umum'],
            'create_incomingletter' => ['admin_umum'],
            'update_incomingletter' => ['admin_umum'],
            'delete_incomingletter' => ['admin_umum'],
            'delete_any_incomingletter' => ['admin_umum'],
            'approve_incomingletter' => ['direktur_keuangan'],
            'reject_incomingletter' => ['direktur_keuangan'],
        ];

        // Define permissions with roles for outgoing letters
        $outgoingLetterPermissions = [
            'view_any_outgoingletter' => ['direktur_keuangan', 'admin_umum'],
            'view_outgoingletter' => ['direktur_keuangan', 'admin_umum'],
            'create_outgoingletter' => ['admin_umum'],
            'update_outgoingletter' => ['admin_umum'],
            'delete_outgoingletter' => ['admin_umum'],
            'delete_any_outgoingletter' => ['admin_umum'],
            'approve_outgoingletter' => ['direktur_keuangan'],
            'reject_outgoingletter' => ['direktur_keuangan'],
        ];

        // Create and assign assignment permissions
        foreach ($assignmentPermissions as $permission => $roles) {
            $permissionModel = Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
            
            foreach ($roles as $roleName) {
                $role = Role::where('name', $roleName)->first();
                if ($role) {
                    $role->givePermissionTo($permissionModel);
                }
            }
        }

        // Create and assign incoming letter permissions
        foreach ($incomingLetterPermissions as $permission => $roles) {
            $permissionModel = Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
            
            foreach ($roles as $roleName) {
                $role = Role::where('name', $roleName)->first();
                if ($role) {
                    $role->givePermissionTo($permissionModel);
                }
            }
        }

        // Create and assign outgoing letter permissions
        foreach ($outgoingLetterPermissions as $permission => $roles) {
            $permissionModel = Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
            
            foreach ($roles as $roleName) {
                $role = Role::where('name', $roleName)->first();
                if ($role) {
                    $role->givePermissionTo($permissionModel);
                }
            }
        }
    }

    private function createTestUsers()
    {
        // HRD users
        $this->createUser('HRD User', 'hrd@example.com', 'password', 'hrd');
        
        // Manager users
        $this->createUser('Manager User', 'manager@example.com', 'password', 'manager');
        
        // Finance Department
        $this->createUser('Direktur Keuangan', 'direktur_keuangan@example.com', 'password', 'direktur_keuangan');
        $this->createUser('Admin Keuangan', 'admin_keuangan@example.com', 'password', 'admin_keuangan');
        $this->createUser('Staff Keuangan', 'staff_keuangan@example.com', 'password', 'staff_keuangan');
        
        // Staff users
        $this->createUser('Staff User', 'staff@example.com', 'password', 'staff');
        
        // Admin
        $this->createUser('Admin Umum', 'admin@example.com', 'password', 'admin_umum');
    }

    private function createUser($name, $email, $password, $role)
    {
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => bcrypt($password),
            ]
        );

        $user->assignRole($role);

        return $user;
    }
}