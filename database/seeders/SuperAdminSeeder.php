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
        User::create([
            'name' => 'Super Admin',
            'email' => 'admin1@example.com',
            'password' => Hash::make('password'),
            'gender' => 'Laki-laki',
            'phone' => '081234567890',
        ]);
    }
}