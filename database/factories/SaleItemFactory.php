<?php

namespace Database\Factories;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Species;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleItem>
 */
class SaleItemFactory extends Factory
{
    protected $model = SaleItem::class;

    public function definition(): array
    {
        $kg = fake()->randomFloat(2, 1, 50);
        $price = fake()->randomFloat(2, 10, 120);

        return [
            'sale_id' => Sale::factory(),
            'species_id' => Species::factory(),
            'weight_kg' => $kg,
            'price_per_kg' => $price,
            'total' => round($kg * $price, 2),
        ];
    }
}
