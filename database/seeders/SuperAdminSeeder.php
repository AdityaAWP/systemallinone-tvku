<?php

namespace Database\Seeders;

use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buat position jika belum ada
        $superAdminPosition = Position::firstOrCreate(
            ['name' => 'Super Admin']
        );
        
        $adminPosition = Position::firstOrCreate(
            ['name' => 'Admin']
        );
        
        $staffPosition = Position::firstOrCreate(
            ['name' => 'Staff']
        );
        
        // Buat super admin
        User::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'is_admin' => true,
            'position_id' => $superAdminPosition->id,
            'gender' => 'Laki-laki',
            'phone' => '081234567890',
        ]);
    }
}