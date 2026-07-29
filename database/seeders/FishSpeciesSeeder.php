<?php

namespace Database\Seeders;

use App\Models\FishSpecies;
use Illuminate\Database\Seeder;

class FishSpeciesSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['هامور', 'الشعري', 'النقرور', 'الكنعد', 'الصافي', 'الزبيدي', 'البياح', 'السبيطي', 'الروبيان', 'السردين'] as $name) {
            FishSpecies::query()->firstOrCreate(['name_ar' => $name]);
        }
    }
}
