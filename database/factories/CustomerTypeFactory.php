<?php

namespace Database\Factories;

use App\Models\CustomerType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerType>
 */
class CustomerTypeFactory extends Factory
{
    protected $model = CustomerType::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'name_en' => fake()->words(2, true),
            'active' => true,
            'display_order' => fake()->numberBetween(0, 20),
        ];
    }
}
