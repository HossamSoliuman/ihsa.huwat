<?php

namespace Database\Factories;

use App\Models\DalalPayout;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DalalPayout>
 */
class DalalPayoutFactory extends Factory
{
    protected $model = DalalPayout::class;

    public function definition(): array
    {
        return [
            'dalal_id' => User::factory()->dalal(),
            'owner_id' => User::factory()->owner(),
            'amount' => fake()->randomFloat(2, 100, 2000),
            'paid_at' => now(),
        ];
    }
}
