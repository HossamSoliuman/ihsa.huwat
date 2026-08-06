<?php

namespace Database\Seeders;

use App\Models\HullMaterial;
use Illuminate\Database\Seeder;

class HullMaterialSeeder extends Seeder
{
    /** Shipped defaults from `config/information.php`; existing rows are left as edited. */
    public function run(): void
    {
        $sortOrder = 0;

        foreach ((array) config('information.hull_materials', []) as $code => $name) {
            HullMaterial::query()->firstOrCreate(
                ['code' => (string) $code],
                ['name' => (string) $name, 'sort_order' => $sortOrder += 10],
            );
        }
    }
}
