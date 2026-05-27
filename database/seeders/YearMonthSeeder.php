<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Admin\Models\Year;
use Modules\Admin\Models\Month;
use Carbon\Carbon;

class YearMonthSeeder extends Seeder
{
    public function run(): void
    {
        $current = (int) Carbon::now()->format('Y');
        $start = $current - 9; // last 10 years including current

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

        foreach (range($start, $current) as $yearNum) {
            $year = Year::firstOrCreate(['year' => $yearNum]);

            foreach ($monthNames as $num => $name) {
                Month::firstOrCreate([
                    'number' => $num,
                    'year_id' => $year->id,
                ], [
                    'name' => $name . ' ' . $yearNum,
                ]);
            }
        }
    }
}
