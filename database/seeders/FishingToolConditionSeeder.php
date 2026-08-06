<?php

namespace Database\Seeders;

use App\Models\FishingToolCondition;
use Illuminate\Database\Seeder;

class FishingToolConditionSeeder extends Seeder
{
    /** Shipped defaults from `config/information.php`; existing rows are left as edited. */
    public function run(): void
    {
        $sortOrder = 0;

        foreach ((array) config('information.fishing_tool_conditions', []) as $code => $name) {
            FishingToolCondition::query()->firstOrCreate(
                ['code' => (string) $code],
                ['name' => (string) $name, 'sort_order' => $sortOrder += 10],
            );
        }
    }
}
