<?php

namespace Database\Factories;

use App\Models\Region;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Region>
 */
class RegionFactory extends Factory
{
    protected $model = Region::class;

    public function definition(): array
    {
        return [
            'name' => 'منطقة '.fake()->unique()->numerify('####'),
            'code' => strtoupper(fake()->lexify('???')),
        ];
    }
}
