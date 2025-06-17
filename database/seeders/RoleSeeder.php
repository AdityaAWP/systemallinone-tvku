<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run()
    {
        // Clear cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define roles
        $roles = [
            'direktur_utama',
            'manager_produksi', 
            'kepala_produksi',
            'kepala_it',
            'manager_teknik',
            'kepala_teknik',
            'kepala_news',
            'manager_keuangan',
            'kepala_marketing',
            'manager_marketing',
            'hrd',
            'staff_produksi',
            'staff_it',
            'staff_teknik',
            'staff_keuangan',
            'admin_keuangan',
            'admin_magang',
            'admin_surat',
            'admin_logistik',
        ];

        // Create roles
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
            $this->command->info("✓ Role created: {$role}");
        }

        // Check if permissions already exist (created by Shield)
        $existingPermissions = Permission::count();
        
        if ($existingPermissions == 0) {
            $this->command->info('No permissions found. Creating permissions...');
            $this->createPermissions();
        } else {
            $this->command->info("Found {$existingPermissions} existing permissions. Using existing permissions.");
        }

        // Define role permissions mapping with Shield format
        $rolePermissions = $this->getRolePermissionsMapping();

        // Assign permissions to roles using Shield format
        foreach ($rolePermissions as $roleName => $permissions) {
            $role = Role::where('name', $roleName)->first();
            
            if ($role) {
                // Filter permissions that actually exist
                $existingPermissionNames = Permission::whereIn('name', $permissions)->pluck('name')->toArray();
                
                if (!empty($existingPermissionNames)) {
                    $role->syncPermissions($existingPermissionNames);
                    $this->command->info("✓ Assigned " . count($existingPermissionNames) . " permissions to {$roleName}");
                } else {
                    $this->command->warn("⚠ No valid permissions found for {$roleName}");
                }
            }
        }

        $this->command->info('✅ Roles and permissions have been seeded successfully!');
    }

    /**
     * Create permissions if they don't exist
     */
    private function createPermissions()
    {
        $resources = [
            'assignment', 'daily_report', 'event', 'incoming_letter', 
            'intern', 'intern_school', 'leave', 'loan_item', 
            'logistics', 'outgoing_letter', 'overtime', 'user'
        ];

        $actions = ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'];

        foreach ($resources as $resource) {
            foreach ($actions as $action) {
                // Create with Shield format
                Permission::firstOrCreate([
                    'name' => $resource . '::' . $action,
                    'guard_name' => 'web'
                ]);
            }
        }
    }

    /**
     * Get role permissions mapping using Shield format (action_resource)
     */
    private function getRolePermissionsMapping()
    {
        return [
            'direktur_utama' => [
                'view_any_assignment', 'view_assignment', 'create_assignment', 
                'update_assignment', 'delete_assignment', 'delete_any_assignment',
                'view_any_event', 'view_event', 'create_event', 
                'update_event', 'delete_event', 'delete_any_event',
            ],
            
            'manager_produksi' => [
                'view_any_leave', 'view_leave', 'create_leave', 
                'update_leave', 'delete_leave', 'delete_any_leave',
                'view_any_daily::report', 'view_daily::report', 'create_daily::report', 
                'update_daily::report', 'delete_daily::report', 'delete_any_daily::report',
                'view_any_overtime', 'view_overtime', 'create_overtime', 
                'update_overtime', 'delete_overtime', 'delete_any_overtime',
                'view_any_event', 'view_event', 'create_event', 
                'update_event', 'delete_event', 'delete_any_event',
            ],

            'kepala_produksi' => [
                'view_any_leave', 'view_leave', 'create_leave', 
                'update_leave', 'delete_leave', 'delete_any_leave',
                'view_any_daily::report', 'view_daily::report', 'create_daily::report', 
                'update_daily::report', 'delete_daily::report', 'delete_any_daily::report',
                'view_any_overtime', 'view_overtime', 'create_overtime', 
                'update_overtime', 'delete_overtime', 'delete_any_overtime',
                'view_any_event', 'view_event', 'create_event', 
                'update_event', 'delete_event', 'delete_any_event',
            ],

            'kepala_it' => [
                'view_any_leave', 'view_leave', 'create_leave', 
                'update_leave', 'delete_leave', 'delete_any_leave',
                'view_any_daily::report', 'view_daily::report', 'create_daily::report', 
                'update_daily::report', 'delete_daily::report', 'delete_any_daily::report',
                'view_any_overtime', 'view_overtime', 'create_overtime', 
                'update_overtime', 'delete_overtime', 'delete_any_overtime',
                'view_any_event', 'view_event', 'create_event', 
                'update_event', 'delete_event', 'delete_any_event',
            ],

            'manager_teknik' => [
                'view_any_leave', 'view_leave', 'create_leave', 
                'update_leave', 'delete_leave', 'delete_any_leave',
                'view_any_daily::report', 'view_daily::report', 'create_daily::report', 
                'update_daily::report', 'delete_daily::report', 'delete_any_daily::report',
                'view_any_overtime', 'view_overtime', 'create_overtime', 
                'update_overtime', 'delete_overtime', 'delete_any_overtime',
                'view_any_event', 'view_event', 'create_event', 
                'update_event', 'delete_event', 'delete_any_event',
            ],

            'kepala_teknik' => [
                'view_any_leave', 'view_leave', 'create_leave', 
                'update_leave', 'delete_leave', 'delete_any_leave',
                'view_any_daily::report', 'view_daily::report', 'create_daily::report', 
                'update_daily::report', 'delete_daily::report', 'delete_any_daily::report',
                'view_any_overtime', 'view_overtime', 'create_overtime', 
                'update_overtime', 'delete_overtime', 'delete_any_overtime',
                'view_any_event', 'view_event', 'create_event', 
                'update_event', 'delete_event', 'delete_any_event',
            ],

            'kepala_news' => [
                'view_any_leave', 'view_leave', 'create_leave', 
                'update_leave', 'delete_leave', 'delete_any_leave',
                'view_any_daily::report', 'view_daily::report', 'create_daily::report', 
                'update_daily::report', 'delete_daily::report', 'delete_any_daily::report',
                'view_any_overtime', 'view_overtime', 'create_overtime', 
                'update_overtime', 'delete_overtime', 'delete_any_overtime',
                'view_any_event', 'view_event', 'create_event', 
                'update_event', 'delete_event', 'delete_any_event',
            ],

            'manager_keuangan' => [
                'view_any_assignment', 'view_assignment', 'create_assignment', 
                'update_assignment', 'delete_assignment', 'delete_any_assignment',
                'view_any_event', 'view_event', 'create_event', 
                'update_event', 'delete_event', 'delete_any_event',
            ],

            'kepala_marketing' => [
                'view_any_leave', 'view_leave', 'create_leave', 
                'update_leave', 'delete_leave', 'delete_any_leave',
                'view_any_daily::report', 'view_daily::report', 'create_daily::report', 
                'update_daily::report', 'delete_daily::report', 'delete_any_daily::report',
                'view_any_overtime', 'view_overtime', 'create_overtime', 
                'update_overtime', 'delete_overtime', 'delete_any_overtime',
                'view_any_event', 'view_event', 'create_event', 
                'update_event', 'delete_event', 'delete_any_event',
            ],

            'manager_marketing' => [
                'view_any_leave', 'view_leave', 'create_leave', 
                'update_leave', 'delete_leave', 'delete_any_leave',
                'view_any_daily::report', 'view_daily::report', 'create_daily::report', 
                'update_daily::report', 'delete_daily::report', 'delete_any_daily::report',
                'view_any_overtime', 'view_overtime', 'create_overtime', 
                'update_overtime', 'delete_overtime', 'delete_any_overtime',
                'view_any_event', 'view_event', 'create_event', 
                'update_event', 'delete_event', 'delete_any_event',
            ],

            'hrd' => [
                'view_any_user', 'view_user', 
                'view_any_leave', 'view_leave', 'create_leave', 
                'update_leave', 'delete_leave', 'delete_any_leave',
                'view_any_daily::report', 'view_daily::report', 'create_daily::report', 
                'update_daily::report', 'delete_daily::report', 'delete_any_daily::report',
                'view_any_overtime', 'view_overtime', 'create_overtime', 
                'update_overtime', 'delete_overtime', 'delete_any_overtime',
                'view_any_event', 'view_event', 'create_event', 
                'update_event', 'delete_event', 'delete_any_event',
            ],

            'staff_produksi' => [
                'view_any_leave', 'view_leave', 'create_leave', 
                'update_leave', 'delete_leave', 'delete_any_leave',
                'view_any_daily::report', 'view_daily::report', 'create_daily::report', 
                'update_daily::report', 'delete_daily::report', 'delete_any_daily::report',
                'view_any_overtime', 'view_overtime', 'create_overtime', 
                'update_overtime', 'delete_overtime', 'delete_any_overtime',
                'view_any_event', 'view_event', 'create_event', 
                'update_event', 'delete_event', 'delete_any_event',
                'view_any_loan::item', 'view_loan::item', 'create_loan::item', 
                'update_loan::item', 'delete_loan::item', 'delete_any_loan::item',
            ],

            'staff_it' => [
                'view_any_leave', 'view_leave', 'create_leave', 
                'update_leave', 'delete_leave', 'delete_any_leave',
                'view_any_daily::report', 'view_daily::report', 'create_daily::report', 
                'update_daily::report', 'delete_daily::report', 'delete_any_daily::report',
                'view_any_overtime', 'view_overtime', 'create_overtime', 
                'update_overtime', 'delete_overtime', 'delete_any_overtime',
                'view_any_event', 'view_event', 'create_event', 
                'update_event', 'delete_event', 'delete_any_event',
                'view_any_loan::item', 'view_loan::item', 'create_loan::item', 
                'update_loan::item', 'delete_loan::item', 'delete_any_loan::item',
            ],

            'staff_teknik' => [
                'view_any_leave', 'view_leave', 'create_leave', 
                'update_leave', 'delete_leave', 'delete_any_leave',
                'view_any_daily::report', 'view_daily::report', 'create_daily::report', 
                'update_daily::report', 'delete_daily::report', 'delete_any_daily::report',
                'view_any_overtime', 'view_overtime', 'create_overtime', 
                'update_overtime', 'delete_overtime', 'delete_any_overtime',
                'view_any_event', 'view_event', 'create_event', 
                'update_event', 'delete_event', 'delete_any_event',
                'view_any_loan::item', 'view_loan::item', 'create_loan::item', 
                'update_loan::item', 'delete_loan::item', 'delete_any_loan::item',
            ],

            'staff_keuangan' => [
                'view_any_assignment', 'view_assignment', 'create_assignment', 
                'update_assignment', 'delete_assignment', 'delete_any_assignment',
                'view_any_event', 'view_event', 'create_event', 
                'update_event', 'delete_event', 'delete_any_event',
            ],

            'admin_keuangan' => [
                'view_any_assignment', 'view_assignment', 'create_assignment', 
                'update_assignment', 'delete_assignment', 'delete_any_assignment',
                'view_any_event', 'view_event', 'create_event', 
                'update_event', 'delete_event', 'delete_any_event',
            ],

            'admin_surat' => [
                'view_any_incoming::letter', 'view_incoming::letter', 'create_incoming::letter', 
                'update_incoming::letter', 'delete_incoming::letter', 'delete_any_incoming::letter',
                'view_any_outgoing::letter', 'view_outgoing::letter', 'create_outgoing::letter', 
                'update_outgoing::letter', 'delete_outgoing::letter', 'delete_any_outgoing::letter',
            ],

            'admin_magang' => [
                'view_any_intern', 'view_intern', 'create_intern', 
                'update_intern', 'delete_intern', 'delete_any_intern',
                'view_any_intern::school', 'view_intern::school', 'create_intern::school', 
                'update_intern::school', 'delete_intern::school', 'delete_any_intern::school',
                'view_any_event', 'view_event', 'create_event', 
                'update_event', 'delete_event', 'delete_any_event',
            ],

            'admin_logistik' => [
                'view_any_logistics', 'view_logistics', 'create_logistics', 
                'update_logistics', 'delete_logistics', 'delete_any_logistics',
                'view_any_loan::item', 'view_loan::item', 'create_loan::item', 
                'update_loan::item', 'delete_loan::item', 'delete_any_loan::item',
                'view_any_event', 'view_event', 'create_event', 
                'update_event', 'delete_event', 'delete_any_event',
            ],
        ];
    }
}