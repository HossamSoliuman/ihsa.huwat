<?php

namespace Database\Seeders;

use App\Models\CrewRole;
use Illuminate\Database\Seeder;

class CrewRoleSeeder extends Seeder
{
    /** Shipped defaults from `config/information.php`; existing rows are left as edited. */
    public function run(): void
    {
        $sortOrder = 0;

        foreach ((array) config('information.crew_roles', []) as $code => $name) {
            CrewRole::query()->firstOrCreate(
                ['code' => (string) $code],
                ['name' => (string) $name, 'sort_order' => $sortOrder += 10],
            );
        }
    }
}
