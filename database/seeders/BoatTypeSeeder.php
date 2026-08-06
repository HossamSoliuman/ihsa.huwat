<?php

namespace Database\Seeders;

use App\Models\BoatType;
use Illuminate\Database\Seeder;

class BoatTypeSeeder extends Seeder
{
    /** Shipped defaults from `config/information.php`; existing rows are left as edited. */
    public function run(): void
    {
        $sortOrder = 0;

        foreach ((array) config('information.boat_types', []) as $code => $name) {
            BoatType::query()->firstOrCreate(
                ['code' => (string) $code],
                ['name' => (string) $name, 'sort_order' => $sortOrder += 10],
            );
        }
    }
}
