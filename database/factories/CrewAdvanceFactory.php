<?php

namespace Database\Factories;

use App\Models\CrewAdvance;
use App\Models\Fisher;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CrewAdvance>
 */
class CrewAdvanceFactory extends Factory
{
    protected $model = CrewAdvance::class;

    public function definition(): array
    {
        return [
            'owner_id' => User::factory()->owner(),
            'member_name' => fake()->name(),
            'date' => now()->toDateString(),
            'amount' => fake()->randomElement([200, 300, 500, 1000]),
        ];
    }

    public function forFisher(Fisher $fisher): static
    {
        return $this->state(fn () => [
            'owner_id' => $fisher->owner_id,
            'fisher_id' => $fisher->id,
            'member_name' => $fisher->name,
            'boat_id' => $fisher->boat_id,
        ]);
    }
}
