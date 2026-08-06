<?php

namespace Database\Seeders;

use App\Models\BoatClassification;
use Illuminate\Database\Seeder;

class BoatClassificationSeeder extends Seeder
{
    /** Shipped defaults from `config/information.php`; existing rows are left as edited. */
    public function run(): void
    {
        $sortOrder = 0;

        foreach ((array) config('information.boat_classifications', []) as $code => $name) {
            BoatClassification::query()->firstOrCreate(
                ['code' => (string) $code],
                ['name' => (string) $name, 'sort_order' => $sortOrder += 10],
            );
        }
    }
}
