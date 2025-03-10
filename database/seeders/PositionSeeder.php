<?php

namespace Database\Seeders;

use App\Models\Position;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $postions = [
            ['name' => 'Super Admin'],
            ['name' => 'Admin'],
            ['name' => 'Manager'],
            ['name' => 'Staff'],
        ];

        foreach ($postions as $position) {
            Position::create($position);
        }
    }
}
