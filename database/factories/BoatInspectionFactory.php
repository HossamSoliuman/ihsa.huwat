<?php

namespace Database\Factories;

use App\Models\Boat;
use App\Models\BoatInspection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BoatInspection>
 */
class BoatInspectionFactory extends Factory
{
    protected $model = BoatInspection::class;

    public function definition(): array
    {
        $date = now()->subDays(fake()->numberBetween(1, 300));

        return [
            'boat_id' => Boat::factory(),
            'inspection_date' => $date->toDateString(),
            'next_due_date' => $date->copy()->addYear()->toDateString(),
            'inspector' => fake()->name(),
            'result' => 'مطابق',
        ];
    }
}
