<?php

namespace Database\Factories;

use App\Models\Boat;
use App\Models\BoatMaintenance;
use App\Models\MaintenanceType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BoatMaintenance>
 */
class BoatMaintenanceFactory extends Factory
{
    protected $model = BoatMaintenance::class;

    public function definition(): array
    {
        return [
            'boat_id' => Boat::factory(),
            'maintenance_type_id' => MaintenanceType::factory(),
            'date' => now()->addDays(fake()->numberBetween(1, 30))->toDateString(),
            'technician' => fake()->name(),
            'estimated_cost' => fake()->randomFloat(2, 500, 20000),
            'status' => 'معلقة',
        ];
    }
}
