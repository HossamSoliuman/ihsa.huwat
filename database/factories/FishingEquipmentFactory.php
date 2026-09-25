<?php

namespace Database\Factories;

use App\Models\FishingEquipment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FishingEquipment>
 */
class FishingEquipmentFactory extends Factory
{
    protected $model = FishingEquipment::class;

    public function definition(): array
    {
        return [
            'owner_id' => User::factory()->owner(),
            'name' => fake()->randomElement(['شبكة خيشومية', 'قراقير', 'خيط صنارة', 'شبكة جرّ']).' '.fake()->numerify('##'),
            'quantity' => fake()->numberBetween(1, 20),
            'unit_cost' => fake()->randomFloat(2, 20, 800),
            'purchase_date' => now()->subDays(fake()->numberBetween(1, 200))->toDateString(),
            'condition' => 'جيدة',
        ];
    }

    public function ownedBy(User $owner): static
    {
        return $this->state(fn () => ['owner_id' => $owner->id]);
    }
}
