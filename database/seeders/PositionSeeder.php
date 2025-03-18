<?php

namespace Database\Seeders;

use App\Models\Position;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    public function run(): void
    {
        $positions = [
            ['name' => 'Super Admin', 'role' => 'super_admin'],
            ['name' => 'Admin', 'role' => 'admin'],
            ['name' => 'Manager', 'role' => 'user'],
            ['name' => 'Staff', 'role' => 'user'],
        ];

        foreach ($positions as $position) {
            Position::create($position);
        }
    }
}