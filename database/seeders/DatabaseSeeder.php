<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            SettingsSeeder::class,
            UserSeeder::class,
            LoanTypeSeeder::class,
            // LoanPlanSeeder::class,
            // MonthlyDeductionSeeder::class,
            // ExtraPaymentSeeder::class,
            AnnouncementSeeder::class,
            \Modules\Division\database\seeders\DivisionSeeder::class,
        ]);
    }
}
