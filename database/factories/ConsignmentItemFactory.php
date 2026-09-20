<?php

namespace Database\Factories;

use App\Models\Consignment;
use App\Models\ConsignmentItem;
use App\Models\Species;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConsignmentItem>
 */
class ConsignmentItemFactory extends Factory
{
    protected $model = ConsignmentItem::class;

    public function definition(): array
    {
        return [
            'consignment_id' => Consignment::factory(),
            'species_id' => Species::factory(),
            'weight_kg' => fake()->randomFloat(2, 1, 50),
        ];
    }
}
