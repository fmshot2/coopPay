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
            DivisionSeeder::class,
            UserSeeder::class,
            LoanTypeSeeder::class,
            //LoanPlanSeeder::class,
            //MonthlyDeductionSeeder::class,
            //ExtraPaymentSeeder::class,
            AnnouncementSeeder::class,
            YearMonthSeeder::class,
        ]);
    }
}
