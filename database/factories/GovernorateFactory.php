<?php

namespace Database\Factories;

use App\Models\Governorate;
use App\Models\Region;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Governorate>
 */
class GovernorateFactory extends Factory
{
    protected $model = Governorate::class;

    public function definition(): array
    {
        return [
            'region_id' => Region::factory(),
            'name' => 'محافظة '.fake()->unique()->numerify('####'),
            'coastal' => true,
        ];
    }
}
