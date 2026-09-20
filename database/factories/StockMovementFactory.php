<?php

namespace Database\Factories;

use App\Models\Species;
use App\Models\StockMovement;
use App\Models\StockMovementType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
{
    protected $model = StockMovement::class;

    public function definition(): array
    {
        $kg = fake()->randomFloat(2, 1, 100);

        return [
            'holder_id' => User::factory()->owner(),
            'species_id' => Species::factory(),
            'stock_movement_type_id' => StockMovementType::factory(),
            'weight_kg' => $kg,
            'balance_after' => $kg,
        ];
    }
}
