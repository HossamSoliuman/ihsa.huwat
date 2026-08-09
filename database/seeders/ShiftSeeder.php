<?php

namespace Database\Seeders;

use App\Models\Shift;
use Illuminate\Database\Seeder;

class ShiftSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['code' => 'morning', 'start_time' => '06:00:00', 'end_time' => '14:00:00', 'crosses_midnight' => false],
            ['code' => 'evening', 'start_time' => '14:00:00', 'end_time' => '22:00:00', 'crosses_midnight' => false],
            ['code' => 'night', 'start_time' => '22:00:00', 'end_time' => '06:00:00', 'crosses_midnight' => true],
        ] as $shift) {
            Shift::query()->firstOrCreate(
                ['code' => $shift['code']],
                [
                    ...$shift,
                    'name' => (string) config('attendance.shifts.'.$shift['code']),
                    'grace_minutes' => 15,
                    'is_active' => true,
                ],
            );
        }
    }
}
