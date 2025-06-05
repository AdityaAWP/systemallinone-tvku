<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class SuperAdminSeeder extends Seeder
{
    public function run()
    {
        // Create super admin role
        $superAdminRole = Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'web'
        ]);

        // Give super admin all permissions
        $superAdminRole->givePermissionTo(Permission::all());

        // Create super admin user (optional)
        $superAdmin = User::firstOrCreate([
            'email' => 'admin@example.com'
        ], [
            'name' => 'Super Admin',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        // Assign super admin role
        $superAdmin->assignRole('super_admin');

        $this->command->info('✅ Super Admin created successfully!');
        $this->command->info('Email: admin@example.com');
        $this->command->info('Password: password');
    }
}