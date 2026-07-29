<?php

namespace Database\Seeders;

use App\Models\Shift;
use Illuminate\Database\Seeder;

class ShiftSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'morning', 'start_time' => '06:00:00', 'end_time' => '14:00:00'],
            ['name' => 'evening', 'start_time' => '14:00:00', 'end_time' => '22:00:00'],
            ['name' => 'night', 'start_time' => '22:00:00', 'end_time' => '06:00:00'],
        ] as $shift) {
            Shift::query()->updateOrCreate(['name' => $shift['name']], $shift);
        }
    }
}
