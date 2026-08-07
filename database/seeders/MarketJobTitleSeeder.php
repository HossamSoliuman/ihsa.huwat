<?php

namespace Database\Seeders;

use App\Models\MarketJobTitle;
use Illuminate\Database\Seeder;

class MarketJobTitleSeeder extends Seeder
{
    /** Shipped defaults from `config/information.php`; existing rows are left as edited. */
    public function run(): void
    {
        $sortOrder = 0;

        foreach ((array) config('information.market_job_titles', []) as $code => $name) {
            MarketJobTitle::query()->firstOrCreate(
                ['code' => (string) $code],
                ['name' => (string) $name, 'sort_order' => $sortOrder += 10],
            );
        }
    }
}
