<?php

namespace Database\Seeders;

use App\Models\FishingMethod;
use Illuminate\Database\Seeder;

class FishingMethodSeeder extends Seeder
{
    /** Shipped defaults from `config/information.php`; existing rows are left as edited. */
    public function run(): void
    {
        $sortOrder = 0;

        foreach ((array) config('information.fishing_methods', []) as $code => $name) {
            FishingMethod::query()->firstOrCreate(
                ['code' => (string) $code],
                ['name' => (string) $name, 'sort_order' => $sortOrder += 10],
            );
        }
    }
}
