<?php

namespace Modules\Division\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Division\Models\Division;

class DivisionSeeder extends Seeder
{
    public function run(): void
    {
        $divisions = [
            [
                'name' => 'Main Division',
                'description' => 'The primary division for all members',
                'is_active' => true,
            ],
            [
                'name' => 'North Division',
                'description' => 'Division for northern region members',
                'is_active' => true,
            ],
            [
                'name' => 'South Division',
                'description' => 'Division for southern region members',
                'is_active' => true,
            ],
            [
                'name' => 'East Division',
                'description' => 'Division for eastern region members',
                'is_active' => true,
            ],
            [
                'name' => 'West Division',
                'description' => 'Division for western region members',
                'is_active' => true,
            ],
        ];

        foreach ($divisions as $division) {
            Division::create($division);
        }
    }
}
