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
        
        // Define permissions for each resource type
        $resources = ['financial_assignment_letter', 'incoming_letter', 'outgoing_letter'];
        $permissions = [];
        
        foreach ($resources as $resource) {
            $permissions = array_merge($permissions, [
                ['name' => 'view_any_' . $resource, 'guard_name' => 'web'],
                ['name' => 'view_' . $resource, 'guard_name' => 'web'],
                ['name' => 'create_' . $resource, 'guard_name' => 'web'],
                ['name' => 'update_' . $resource, 'guard_name' => 'web'],
                ['name' => 'delete_' . $resource, 'guard_name' => 'web'],
                ['name' => 'delete_any_' . $resource, 'guard_name' => 'web'],
            ]);
        }
        
        // Insert all permissions
        Permission::insert($permissions);
        
        // Assign permissions to roles
        
        // Direktur Utama - Full access to all resources
        $direkturUtamaRole->givePermissionTo(
            Permission::whereIn('name', [
                'view_any_financial_assignment_letter',
                'view_financial_assignment_letter',
                'create_financial_assignment_letter',
                'update_financial_assignment_letter',
                'delete_financial_assignment_letter',
                'delete_any_financial_assignment_letter',
                
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
            ])->pluck('name')->toArray()
        );
        
        // Manager Keuangan - Full access to financial_assignment_letter only
        $managerKeuanganRole->givePermissionTo(
            Permission::whereIn('name', [
                'view_any_financial_assignment_letter',
                'view_financial_assignment_letter',
                'create_financial_assignment_letter',
                'update_financial_assignment_letter',
                'delete_financial_assignment_letter',
                'delete_any_financial_assignment_letter',
            ])->pluck('name')->toArray()
        );
        
        // Staff Keuangan - Limited access to financial_assignment_letter (no delete_any)
        $staffKeuanganRole->givePermissionTo(
            Permission::whereIn('name', [
                'view_any_financial_assignment_letter',
                'view_financial_assignment_letter',
                'create_financial_assignment_letter',
                'update_financial_assignment_letter',
                'delete_financial_assignment_letter',
            ])->pluck('name')->toArray()
        );
        
        // Admin Umum - Full access to incoming and outgoing letters only
        $adminUmumRole->givePermissionTo(
            Permission::whereIn('name', [
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
            ])->pluck('name')->toArray()
        );
        
        // Create sample users for each role
        $direktur = User::create([
            'name' => 'Direktur Utama',
            'email' => 'direktur@example.com',
            'password' => bcrypt('password'),
        ]);
        $direktur->assignRole($direkturUtamaRole);
        
        $manager = User::create([
            'name' => 'Manager Keuangan',
            'email' => 'manager@example.com',
            'password' => bcrypt('password'),
        ]);
        $manager->assignRole($managerKeuanganRole);
        
        $staff = User::create([
            'name' => 'Staff Keuangan',
            'email' => 'staff@example.com',
            'password' => bcrypt('password'),
        ]);
        $staff->assignRole($staffKeuanganRole);
        
        $admin = User::create([
            'name' => 'Admin Umum',
            'email' => 'adminumum@example.com',
            'password' => bcrypt('password'),
        ]);
        $admin->assignRole($adminUmumRole);
    }
}