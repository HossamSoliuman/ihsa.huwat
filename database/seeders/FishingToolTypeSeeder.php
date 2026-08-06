<?php

namespace Database\Seeders;

use App\Models\FishingToolType;
use Illuminate\Database\Seeder;

class FishingToolTypeSeeder extends Seeder
{
    /** Shipped defaults from `config/information.php`; existing rows are left as edited. */
    public function run(): void
    {
        $sortOrder = 0;

        foreach ((array) config('information.fishing_tool_types', []) as $code => $name) {
            FishingToolType::query()->firstOrCreate(
                ['code' => (string) $code],
                ['name' => (string) $name, 'sort_order' => $sortOrder += 10],
            );
        }
    }
}
