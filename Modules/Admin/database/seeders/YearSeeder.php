<?php

namespace Modules\Admin\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Admin\Models\Year;

class YearSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $current = (int) now()->format('Y');
        $start = $current - 9; // last 10 years including current

        foreach (range($start, $current) as $year) {
            Year::firstOrCreate(['year' => $year]);
        }
    }
}
