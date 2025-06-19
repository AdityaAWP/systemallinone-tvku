<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\InternDivision;

class InternDivisionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $divisions = [
            ['name' => 'IT', 'description' => 'Information Technology'],
            ['name' => 'Produksi', 'description' => 'Production Department'],
            ['name' => 'DINUS FM', 'description' => 'DINUS FM Radio'],
            ['name' => 'TS', 'description' => 'Technical Support'],
            ['name' => 'MCR', 'description' => 'Master Control Room'],
            ['name' => 'DMO', 'description' => 'Digital Media Operations'],
            ['name' => 'Wardrobe', 'description' => 'Wardrobe Department'],
            ['name' => 'News', 'description' => 'News Department'],
            ['name' => 'Humas dan Marketing', 'description' => 'Public Relations and Marketing'],
        ];

        foreach ($divisions as $division) {
            InternDivision::create($division);
        }
    }
}
