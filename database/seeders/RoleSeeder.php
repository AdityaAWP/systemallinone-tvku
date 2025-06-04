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
        }

        // Define resources and their permissions
        $resources = [
            'assignment' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
            'daily_report' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
            'event' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
            'incoming_letter' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
            'intern' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
            'intern_school' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
            'leave' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
            'loan_item' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
            'logistics' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
            'outgoing_letter' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
            'overtime' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
            'user' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
        ];

        // Create permissions
        foreach ($resources as $resource => $actions) {
            foreach ($actions as $action) {
                Permission::firstOrCreate([
                    'name' => $action . '_' . $resource,
                    'guard_name' => 'web'
                ]);
            }
        }

        // Define role permissions mapping
        $rolePermissions = [
            'direktur_utama' => [
                'assignment' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
                'event' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
            ],
            
            'manager_produksi' => [
                'leave' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
                'daily_report' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
                'overtime' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
                'event' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
            ],

            'kepala_produksi' => [
                'leave' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
                'daily_report' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
                'overtime' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
                'event' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
            ],

            'kepala_it' => [
                'leave' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
                'daily_report' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
                'overtime' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
                'event' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
            ],

            'manager_teknik' => [
                'leave' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
                'daily_report' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
                'overtime' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
                'event' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
            ],

            'kepala_teknik' => [
                'leave' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
                'daily_report' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
                'overtime' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
                'event' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
            ],

            'kepala_news' => [
                'leave' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
                'daily_report' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
                'overtime' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
                'event' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
            ],

            'manager_keuangan' => [
                'assignment' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
                'event' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
            ],

            'kepala_marketing' => [
                'leave' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
                'daily_report' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
                'overtime' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
                'event' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
            ],

            'manager_marketing' => [
                'leave' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
                'daily_report' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
                'overtime' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
                'event' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
            ],

            'hrd' => [
                'user' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
                'leave' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
                'daily_report' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
                'overtime' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
                'event' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
            ],

            'staff_produksi' => [
                'leave' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
                'daily_report' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
                'overtime' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
                'event' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
            ],

            'staff_it' => [
                'leave' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
                'daily_report' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
                'overtime' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
                'event' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
            ],

            'staff_teknik' => [
                'leave' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
                'daily_report' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
                'overtime' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
                'event' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
            ],

            'staff_keuangan' => [
                'assignment' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
                'event' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
            ],

            'admin_keuangan' => [
                'assignment' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
                'event' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
            ],
            'admin_surat' => [
                'incoming_letter' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
                'outgoing_letter' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
            ],
            'admin_magang' => [
                'intern' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
                'intern_school' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
                'event' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
            ],

            'admin_logistik' => [
                'logistics' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
                'loan_item' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
                'event' => ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'],
            ],
        ];

        foreach ($rolePermissions as $roleName => $resourcePermissions) {
            $role = Role::where('name', $roleName)->first();
            
            if ($role) {
                $permissions = [];
                
                foreach ($resourcePermissions as $resource => $actions) {
                    foreach ($actions as $action) {
                        $permissions[] = $action . '_' . $resource;
                    }
                }
                
                $role->syncPermissions($permissions);
            }
        }

        $this->command->info('Roles and permissions have been seeded successfully!');
    }
}