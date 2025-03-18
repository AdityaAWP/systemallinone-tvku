<?php

namespace Database\Seeders;

use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $superAdminPosition = Position::firstOrCreate(
            ['name' => 'Super Admin', 'role' => 'super_admin']
        );
        
        $adminPosition = Position::firstOrCreate(
            ['name' => 'Admin', 'role' => 'admin']
        );
        
        $staffPosition = Position::firstOrCreate(
            ['name' => 'Staff', 'role' => 'user']
        );
        
        User::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'is_admin' => true,
            'position_id' => $superAdminPosition->id,
            'gender' => 'Laki-laki',
            'phone' => '081234567890',
        ]);
    }
}