<?php

namespace Database\Seeders;

use App\Models\Region;
use App\Models\Season;
use Illuminate\Database\Seeder;

class SeasonSeeder extends Seeder
{
    public function run(): void
    {
        $regions = Region::query()->get();

        if ($regions->isEmpty()) {
            return;
        }

        Season::factory()
            ->count(12)
            ->state(fn (): array => ['region_id' => $regions->random()->id])
            ->create();
    }
}
