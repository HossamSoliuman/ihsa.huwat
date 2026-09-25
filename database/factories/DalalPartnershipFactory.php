<?php

namespace Database\Factories;

use App\Models\DalalPartnership;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DalalPartnership>
 */
class DalalPartnershipFactory extends Factory
{
    protected $model = DalalPartnership::class;

    public function definition(): array
    {
        return [
            'owner_id' => User::factory()->owner(),
            'dalal_id' => User::factory()->dalal(),
            'commission_pct' => 5,
            'wage_pct' => 2,
            'message' => fake()->sentence(),
            'status' => DalalPartnership::PENDING,
        ];
    }

    public function accepted(): static
    {
        return $this->state(fn () => ['status' => DalalPartnership::ACCEPTED, 'responded_at' => now()]);
    }
}
