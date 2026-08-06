<?php

namespace Database\Seeders;

use App\Models\FishingToolMaterial;
use Illuminate\Database\Seeder;

class FishingToolMaterialSeeder extends Seeder
{
    /** Shipped defaults from `config/information.php`; existing rows are left as edited. */
    public function run(): void
    {
        $sortOrder = 0;

        foreach ((array) config('information.fishing_tool_materials', []) as $code => $name) {
            FishingToolMaterial::query()->firstOrCreate(
                ['code' => (string) $code],
                ['name' => (string) $name, 'sort_order' => $sortOrder += 10],
            );
        }
    }
}
