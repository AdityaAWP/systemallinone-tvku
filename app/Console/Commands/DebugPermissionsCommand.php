<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DebugPermissionsCommand extends Command
{
    protected $signature = 'permission:debug {--fix : Fix permission assignments}';
    protected $description = 'Debug permission issues and optionally fix them';

    public function handle()
    {
        $this->info('🔍 Debugging Permissions...');
        $this->newLine();

        // Check all permissions
        $this->checkPermissions();
        
        // Check roles
        $this->checkRoles();
        
        // Check assignments
        $this->checkRolePermissionAssignments();

        if ($this->option('fix')) {
            $this->fixPermissions();
        }
    }

    private function checkPermissions()
    {
        $this->info('📋 All Permissions:');
        $permissions = Permission::all();
        
        if ($permissions->isEmpty()) {
            $this->error('❌ No permissions found!');
            return;
        }

        $this->table(['ID', 'Name', 'Guard'], $permissions->map(function($p) {
            return [$p->id, $p->name, $p->guard_name];
        })->toArray());
        
        $this->info("Total permissions: " . $permissions->count());
        $this->newLine();
    }

    private function checkRoles()
    {
        $this->info('👥 All Roles:');
        $roles = Role::with('permissions')->get();
        
        foreach ($roles as $role) {
            $permissionCount = $role->permissions->count();
            $status = $permissionCount > 0 ? '✅' : '❌';
            $this->line("{$status} {$role->name} ({$permissionCount} permissions)");
        }
        $this->newLine();
    }

    private function checkRolePermissionAssignments()
    {
        $this->info('🔗 Role-Permission Assignments:');
        
        $problematicRoles = [];
        $roles = Role::with('permissions')->get();
        
        foreach ($roles as $role) {
            if ($role->permissions->isEmpty() && $role->name !== 'super_admin') {
                $problematicRoles[] = $role->name;
            }
        }

        if (!empty($problematicRoles)) {
            $this->error('❌ Roles without permissions:');
            foreach ($problematicRoles as $roleName) {
                $this->line("   • {$roleName}");
            }
        } else {
            $this->info('✅ All roles have permissions assigned');
        }
        $this->newLine();
    }

    private function fixPermissions()
    {
        $this->info('🔧 Fixing permissions...');
        
        // Clear cache first
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        
        // Re-run the seeder
        $this->call('db:seed', ['--class' => 'RoleSeeder']);
        
        $this->info('✅ Permissions fixed!');
    }
}