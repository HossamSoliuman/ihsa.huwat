<?php

namespace Database\Seeders;

use App\Models\Nationality;
use Illuminate\Database\Seeder;

class NationalitySeeder extends Seeder
{
    /**
     * The list the portal shipped with, taken from `config/information.php`. Rows are
     * only created where they are missing, so a name the information centre has since
     * edited is never seeded back over.
     */
    public function run(): void
    {
        $sortOrder = 0;

        foreach ((array) config('information.nationalities', []) as $code => $name) {
            Nationality::query()->firstOrCreate(
                ['code' => (string) $code],
                ['name' => (string) $name, 'sort_order' => $sortOrder += 10],
            );
        }
    }
}
