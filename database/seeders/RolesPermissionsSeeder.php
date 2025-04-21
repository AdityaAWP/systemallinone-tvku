<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles
        $direkturUtamaRole = Role::create(['name' => 'direktur_utama', 'guard_name' => 'web']);
        $managerKeuanganRole = Role::create(['name' => 'manager_keuangan', 'guard_name' => 'web']);
        $staffKeuanganRole = Role::create(['name' => 'staff_keuangan', 'guard_name' => 'web']);
        $adminUmumRole = Role::create(['name' => 'admin_umum', 'guard_name' => 'web']);
        
        // Define permissions manually instead of using Utils::getResourcePermissionPrefixes()
        $permissionPrefixes = ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'];
        
        $resourcePermissions = collect($permissionPrefixes)
            ->flatMap(function ($prefix) {
                return [
                    "{$prefix}_financial_assignment_letter",
                    "{$prefix}_incoming_letter",
                    "{$prefix}_outgoing_letter",
                ];
            })
            ->toArray();
            
        $permissions = collect($resourcePermissions)
            ->map(function ($permission) {
                return ['name' => $permission, 'guard_name' => 'web'];
            })
            ->toArray();
            
        Permission::insert($permissions);
        
        // Assign permissions to roles
        
        // Direktur Utama permissions
        $direkturUtamaRole->givePermissionTo([
            'view_any_financial_assignment_letter',
            'view_financial_assignment_letter',
            'create_financial_assignment_letter',
            'update_financial_assignment_letter',
            'delete_financial_assignment_letter',
            'delete_any_financial_assignment_letter',
        ]);
        
        // Manager Keuangan permissions
        $managerKeuanganRole->givePermissionTo([
            'view_any_financial_assignment_letter',
            'view_financial_assignment_letter',
            'create_financial_assignment_letter',
            'update_financial_assignment_letter',
            'delete_financial_assignment_letter',
            'delete_any_financial_assignment_letter',
        ]);
        
        // Staff Keuangan permissions
        $staffKeuanganRole->givePermissionTo([
            'view_any_financial_assignment_letter',
            'view_financial_assignment_letter',
            'create_financial_assignment_letter',
            'update_financial_assignment_letter',
            'delete_financial_assignment_letter',
        ]);
        
        // Admin Umum permissions
        $adminUmumRole->givePermissionTo([
            'view_any_incoming_letter',
            'view_incoming_letter',
            'create_incoming_letter',
            'update_incoming_letter',
            'delete_incoming_letter',
            'delete_any_incoming_letter',
            'view_any_outgoing_letter',
            'view_outgoing_letter',
            'create_outgoing_letter',
            'update_outgoing_letter',
            'delete_outgoing_letter',
            'delete_any_outgoing_letter',
        ]);
        
        // Create sample users for each role
        User::create([
            'name' => 'Direktur Utama',
            'email' => 'direktur@example.com',
            'password' => bcrypt('password'),
        ])->assignRole($direkturUtamaRole);
        
        User::create([
            'name' => 'Manager Keuangan',
            'email' => 'manager@example.com',
            'password' => bcrypt('password'),
        ])->assignRole($managerKeuanganRole);
        
        User::create([
            'name' => 'Staff Keuangan',
            'email' => 'staff@example.com',
            'password' => bcrypt('password'),
        ])->assignRole($staffKeuanganRole);
        
        User::create([
            'name' => 'Admin Umum',
            'email' => 'adminumum@example.com',
            'password' => bcrypt('password'),
        ])->assignRole($adminUmumRole);
    }
}