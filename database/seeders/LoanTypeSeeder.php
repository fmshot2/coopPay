<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Loan\Models\LoanType;

class LoanTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Personal',  'description' => 'Personal loan for individual needs'],
            ['name' => 'Emergency', 'description' => 'Emergency loan for urgent situations'],
            ['name' => 'Housing',   'description' => 'Loan for housing and accommodation'],
            ['name' => 'Project',   'description' => 'Loan for personal or business projects'],
            ['name' => 'Special',   'description' => 'Loan for special projects'],
            ['name' => 'General',   'description' => 'General purpose loan'],
            ['name' => 'Car',       'description' => 'Loan for vehicle purchase'],
            ['name' => 'Others',    'description' => 'Other loan types not listed above'],
        ];

        foreach ($types as $type) {
            LoanType::create($type);
        }
    }
}
