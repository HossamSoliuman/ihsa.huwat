<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\AssetType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Asset>
 */
class AssetFactory extends Factory
{
    protected $model = Asset::class;

    public function definition(): array
    {
        return [
            'owner_id' => User::factory()->owner(),
            'asset_type_id' => AssetType::factory(),
            'name' => fake()->words(2, true),
            'purchase_date' => now()->subMonths(fake()->numberBetween(1, 36))->startOfMonth()->toDateString(),
            'purchase_cost' => 12000,
            'salvage_value' => 0,
            'useful_life_years' => 5,
            'status' => Asset::ACTIVE,
        ];
    }

    public function ownedBy(User $owner): static
    {
        return $this->state(fn () => ['owner_id' => $owner->id]);
    }
}
