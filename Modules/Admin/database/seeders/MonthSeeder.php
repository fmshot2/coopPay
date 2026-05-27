<?php

namespace Modules\Admin\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Admin\Models\Year;
use Modules\Admin\Models\Month;

class MonthSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $monthNames = [
            1  => 'January',
            2  => 'February',
            3  => 'March',
            4  => 'April',
            5  => 'May',
            6  => 'June',
            7  => 'July',
            8  => 'August',
            9  => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December',
        ];

        $years = Year::all();

        foreach ($years as $year) {
            foreach ($monthNames as $number => $name) {
                Month::create([
                    'number' => $number,
                    'year_id' => $year->id,
                    'name'   => $name . ' ' . $year->year,
                ]);
            }
        }
    }
}
